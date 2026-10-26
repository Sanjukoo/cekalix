<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Importacion;
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
            'admin' => view('dashboard.admin', [
                'ventasHoy' => Venta::vigente()->whereDate('fecha_venta', $hoy)->sum('total'),
                'ventasMes' => Venta::vigente()->where('fecha_venta', '>=', $inicioMes)->sum('total'),
                'stockBajo' => Producto::where('stock', '<', 10)->count(),
                'enRecepcion' => Importacion::where('estado', 'En recepción')->count(),
            ]),
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
}
