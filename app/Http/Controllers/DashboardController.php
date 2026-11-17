<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Importacion;
use App\Models\Merma;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // Cada rol ve solo los indicadores que usa en su trabajo diario.
    public function index(Request $request)
    {
        $role = $request->user()->role;

        $hoy = now()->toDateString();
        $inicioMes = now()->startOfMonth()->toDateString();

        return match ($role) {
            'admin' => $this->panelAdministrador(
                $hoy,
                $inicioMes
            ),

            'inventario' => view('dashboard.inventario', [
                'stockBajo' => Producto::where('stock', '<', 10)->count(),
                'enRecepcion' => Importacion::where('estado', 'En recepción')->count(),
                'movimientosHoy' => MovimientoInventario::whereDate('created_at', $hoy)->count(),
            ]),

            'ventas' => view('dashboard.ventas', [
                'ventasHoy' => Venta::vigente()->whereDate('fecha_venta', $hoy)->sum('total'),
                'ventasHoyCantidad' => Venta::vigente()->whereDate('fecha_venta', $hoy)->count(),
                'ventasMes' => Venta::vigente()->where('fecha_venta', '>=', $inicioMes)->sum('total'),
                'totalClientes' => Cliente::count(),
            ]),

            default => abort(403, 'Tu usuario no tiene un rol autorizado'),
        };
    }

    // Prepara los indicadores y gráficos del administrador.
    private function panelAdministrador($hoy, $inicioMes)
    {
        $ahora = now();
        $anio = $ahora->year;

        /*
        |--------------------------------------------------------------------------
        | VENTAS SEMANALES
        |--------------------------------------------------------------------------
        | Se agrupan las ventas vigentes por día de la semana actual.
        */

        $inicioSemana = $ahora->copy()->startOfWeek();
        $finSemana = $ahora->copy()->endOfWeek();

        $ventasSemana = Venta::vigente()
            ->whereBetween('fecha_venta', [
                $inicioSemana->toDateString(),
                $finSemana->toDateString()
            ])
            ->selectRaw('DATE(fecha_venta) as periodo, SUM(total) as total')
            ->groupByRaw('DATE(fecha_venta)')
            ->pluck('total', 'periodo');

        $diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

        $ventasSemanalLabels = [];
        $ventasSemanalDatos = [];

        $fecha = $inicioSemana->copy();

        while ($fecha->lte($finSemana)) {
            $ventasSemanalLabels[] =
                $diasSemana[$fecha->dayOfWeekIso - 1] . ' ' . $fecha->format('d/m');

            $ventasSemanalDatos[] = (float) (
                $ventasSemana[$fecha->toDateString()] ?? 0
            );

            $fecha->addDay();
        }

        /*
        |--------------------------------------------------------------------------
        | VENTAS MENSUALES
        |--------------------------------------------------------------------------
        | Se agrupan las ventas vigentes por día del mes actual.
        */

        $inicioMesFecha = $ahora->copy()->startOfMonth();
        $finMesFecha = $ahora->copy()->endOfMonth();

        $ventasMesPorDia = Venta::vigente()
            ->whereBetween('fecha_venta', [
                $inicioMesFecha->toDateString(),
                $finMesFecha->toDateString()
            ])
            ->selectRaw('DAY(fecha_venta) as periodo, SUM(total) as total')
            ->groupByRaw('DAY(fecha_venta)')
            ->pluck('total', 'periodo');

        $ventasMensualLabels = [];
        $ventasMensualDatos = [];

        for ($dia = 1; $dia <= $finMesFecha->day; $dia++) {
            $ventasMensualLabels[] = (string) $dia;

            $ventasMensualDatos[] = (float) (
                $ventasMesPorDia[$dia] ?? 0
            );
        }

        /*
        |--------------------------------------------------------------------------
        | VENTAS ANUALES
        |--------------------------------------------------------------------------
        | Se agrupan las ventas vigentes por mes del año actual.
        */

        $ventasAnio = Venta::vigente()
            ->whereYear('fecha_venta', $anio)
            ->selectRaw('MONTH(fecha_venta) as periodo, SUM(total) as total')
            ->groupByRaw('MONTH(fecha_venta)')
            ->pluck('total', 'periodo');

        $mesesLabels = [
            'Enero', 'Febrero', 'Marzo', 'Abril',
            'Mayo', 'Junio', 'Julio', 'Agosto',
            'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];

        $ventasAnualLabels = $mesesLabels;
        $ventasAnualDatos = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $ventasAnualDatos[] = (float) (
                $ventasAnio[$mes] ?? 0
            );
        }

        /*
        |--------------------------------------------------------------------------
        | MERMAS POR MES
        |--------------------------------------------------------------------------
        | Se suman las cajas dañadas registradas durante el año actual.
        */

        $mermasPorMes = Merma::query()
            ->whereYear('fecha_registro', $anio)
            ->selectRaw('MONTH(fecha_registro) as periodo, SUM(cantidad) as total')
            ->groupByRaw('MONTH(fecha_registro)')
            ->pluck('total', 'periodo');

        $mermasLabels = $mesesLabels;
        $mermasDatos = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $mermasDatos[] = (int) (
                $mermasPorMes[$mes] ?? 0
            );
        }

        /*
        |--------------------------------------------------------------------------
        | STOCK DE PRODUCTOS
        |--------------------------------------------------------------------------
        | Se muestran los 10 productos con menor stock disponible.
        */

        $productosStock = Producto::query()
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get(['nombre', 'stock']);

        $stockLabels = $productosStock
            ->pluck('nombre')
            ->values();

        $stockDatos = $productosStock
            ->pluck('stock')
            ->map(fn ($stock) => (int) $stock)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | PANEL DEL ADMINISTRADOR
        |--------------------------------------------------------------------------
        */

        return view('dashboard.admin', [
            'ventasHoy' => Venta::vigente()
                ->whereDate('fecha_venta', $hoy)
                ->sum('total'),

            'ventasMes' => Venta::vigente()
                ->where('fecha_venta', '>=', $inicioMes)
                ->sum('total'),

            'stockBajo' => Producto::where('stock', '<', 10)->count(),

            'enRecepcion' => Importacion::where('estado', 'En recepción')->count(),

            // Datos para los gráficos.
            'ventasSemanalLabels' => $ventasSemanalLabels,
            'ventasSemanalDatos' => $ventasSemanalDatos,

            'ventasMensualLabels' => $ventasMensualLabels,
            'ventasMensualDatos' => $ventasMensualDatos,

            'ventasAnualLabels' => $ventasAnualLabels,
            'ventasAnualDatos' => $ventasAnualDatos,

            'mermasLabels' => $mermasLabels,
            'mermasDatos' => $mermasDatos,

            'stockLabels' => $stockLabels,
            'stockDatos' => $stockDatos,
        ]);
    }
}
