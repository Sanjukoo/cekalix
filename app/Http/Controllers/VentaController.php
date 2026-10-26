<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
    // Mostrar el historial de ventas, anuladas incluidas.
    public function index()
    {
        $ventas = Venta::with('usuario', 'cliente')
            ->withCount('detalles')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $totalVendido = Venta::vigente()->sum('total');

        return view('ventas.index', compact('ventas', 'totalVendido'));
    }

    public function create()
    {
        return view('ventas.create', [
            'productos' => $this->productosDisponibles(),
            'clientes' => $this->clientes(),
        ]);
    }

    // Registrar una venta y descontar el stock mediante movimientos de inventario.
    public function store(Request $request)
    {
        $validated = $this->validar($request);

        $venta = DB::transaction(function () use ($validated) {
            $venta = Venta::create([
                'cliente_id' => $validated['cliente_id'] ?? null,
                'fecha_venta' => $validated['fecha_venta'],
                'total' => 0,
                'estado' => Venta::VIGENTE,
                'registrado_por' => auth()->id(),
            ]);

            $venta->update(['total' => $this->registrarLineas($venta, $validated['productos'])]);

            return $venta;
        });

        return redirect()
            ->route('ventas.show', $venta)
            ->with('success', 'Venta registrada correctamente.');
    }

    public function show(Venta $venta)
    {
        $venta->load('usuario', 'cliente', 'anuladoPor', 'detalles.producto');

        return view('ventas.show', compact('venta'));
    }

    public function edit(Venta $venta)
    {
        if ($venta->estaAnulada()) {
            return redirect()
                ->route('ventas.show', $venta)
                ->with('error', 'No se puede editar una venta anulada.');
        }

        $venta->load('detalles');

        return view('ventas.edit', [
            'venta' => $venta,
            'productos' => $this->productosDisponibles($venta),
            'clientes' => $this->clientes(),
        ]);
    }

    // Editar una venta: devuelve el stock de las líneas anteriores y aplica las nuevas, todo en una transacción.
    public function update(Request $request, Venta $venta)
    {
        $validated = $this->validar($request);

        DB::transaction(function () use ($validated, $venta) {
            $ventaActual = Venta::whereKey($venta->id)->lockForUpdate()->firstOrFail();

            if ($ventaActual->estaAnulada()) {
                throw ValidationException::withMessages([
                    'productos' => 'No se puede editar una venta anulada.',
                ]);
            }

            foreach ($ventaActual->detalles()->get() as $detalle) {
                $producto = Producto::whereKey($detalle->producto_id)->lockForUpdate()->firstOrFail();

                MovimientoInventario::registrar(
                    $producto,
                    $detalle->cantidad,
                    'edicion_venta',
                    "Edición de {$ventaActual->numero}: se devuelve la línea anterior",
                    $ventaActual
                );
            }

            $ventaActual->detalles()->delete();

            $ventaActual->update([
                'cliente_id' => $validated['cliente_id'] ?? null,
                'fecha_venta' => $validated['fecha_venta'],
            ]);

            $ventaActual->update(['total' => $this->registrarLineas($ventaActual, $validated['productos'])]);
        });

        return redirect()
            ->route('ventas.show', $venta)
            ->with('success', 'Venta actualizada correctamente.');
    }

    // Anular: no se borra. Se devuelve el stock y la venta queda marcada con su motivo.
    public function anular(Request $request, Venta $venta)
    {
        $validated = $request->validate([
            'motivo_anulacion' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $venta) {
            $ventaActual = Venta::whereKey($venta->id)->lockForUpdate()->firstOrFail();

            if ($ventaActual->estaAnulada()) {
                throw ValidationException::withMessages([
                    'motivo_anulacion' => 'Esta venta ya está anulada.',
                ]);
            }

            foreach ($ventaActual->detalles()->get() as $detalle) {
                $producto = Producto::whereKey($detalle->producto_id)->lockForUpdate()->firstOrFail();

                MovimientoInventario::registrar(
                    $producto,
                    $detalle->cantidad,
                    'anulacion_venta',
                    "Anulación de {$ventaActual->numero}: {$validated['motivo_anulacion']}",
                    $ventaActual
                );
            }

            $ventaActual->update([
                'estado' => Venta::ANULADA,
                'motivo_anulacion' => $validated['motivo_anulacion'],
                'anulado_por' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('ventas.show', $venta)
            ->with('success', 'Venta anulada. El stock fue devuelto al inventario.');
    }

    public function comprobante(Venta $venta)
    {
        $venta->load('usuario', 'cliente', 'detalles.producto');

        return Pdf::loadView('ventas.comprobante', compact('venta'))
            ->setPaper('a4')
            ->download($venta->numero.'.pdf');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'fecha_venta' => 'required|date',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|integer|distinct|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
        ]);
    }

    // Guarda las líneas, registra la salida de stock de cada una y devuelve el total.
    private function registrarLineas(Venta $venta, array $lineas): float
    {
        $total = 0;

        foreach ($lineas as $linea) {
            $producto = Producto::whereKey($linea['producto_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $producto->precio_venta <= 0) {
                throw ValidationException::withMessages([
                    'productos' => "El producto {$producto->codigo} no tiene precio de venta asignado.",
                ]);
            }

            $precioUnitario = (float) $producto->precio_venta;
            $subtotal = round($linea['cantidad'] * $precioUnitario, 2);

            VentaDetalle::create([
                'venta_id' => $venta->id,
                'producto_id' => $producto->id,
                'cantidad' => $linea['cantidad'],
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotal,
            ]);

            MovimientoInventario::registrar(
                $producto,
                -$linea['cantidad'],
                'venta',
                "Venta {$venta->numero}",
                $venta
            );

            $total += $subtotal;
        }

        return $total;
    }

    // Al editar, las unidades de la propia venta también cuentan como disponibles.
    private function productosDisponibles(?Venta $venta = null)
    {
        $enVenta = $venta
            ? $venta->detalles->pluck('cantidad', 'producto_id')
            : collect();

        return Producto::where('precio_venta', '>', 0)
            ->where(function ($query) use ($enVenta) {
                $query->where('stock', '>', 0)
                    ->orWhereIn('id', $enVenta->keys());
            })
            ->orderBy('codigo')
            ->get()
            ->each(function (Producto $producto) use ($enVenta) {
                $producto->stock_disponible = $producto->stock + ($enVenta[$producto->id] ?? 0);
            })
            ->filter(fn (Producto $producto) => $producto->stock_disponible > 0)
            ->values();
    }

    private function clientes()
    {
        return Cliente::orderBy('nombre')->get();
    }
}
