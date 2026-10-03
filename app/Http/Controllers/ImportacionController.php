<?php

namespace App\Http\Controllers;

use App\Models\Importacion;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\ImportacionDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportacionController extends Controller
{
    public function index()
    {
        $importaciones = Importacion::with('proveedor', 'detalles.producto')
            ->orderBy('id', 'desc')
            ->paginate(15);
        return view('importaciones.index', compact('importaciones'));
    }

    public function create()
    {
        $proveedores = Proveedor::activos()->orderBy('razon_social')->get();
        $productos = Producto::orderBy('codigo')->get();
        return view('importaciones.create', compact('proveedores', 'productos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'numero_factura' => 'required|string|max:50',
            'numero_contenedor' => 'required|string|max:50',
            'fecha_llegada' => 'required|date',
            'producto_id' => 'required|exists:productos,id',
            'cajas_facturadas' => 'required|integer|min:1',
        ], [
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'numero_factura.required' => 'El número de factura es obligatorio.',
            'numero_contenedor.required' => 'El número de contenedor es obligatorio.',
            'fecha_llegada.required' => 'La fecha de llegada es obligatoria.',
            'fecha_llegada.date' => 'La fecha debe ser válida.',
            'producto_id.required' => 'Debe seleccionar un producto.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'cajas_facturadas.required' => 'La cantidad de cajas es obligatoria.',
            'cajas_facturadas.min' => 'Debe ingresar al menos 1 caja.',
        ]);

        try {
            // La cabecera y el detalle se guardan juntos o no se guarda nada
            $importacion = DB::transaction(function () use ($validated) {
                $importacion = Importacion::create([
                    'proveedor_id' => $validated['proveedor_id'],
                    'numero_factura' => $validated['numero_factura'],
                    'numero_contenedor' => $validated['numero_contenedor'],
                    'fecha_llegada' => $validated['fecha_llegada'],
                    'estado' => 'En recepción',
                ]);

                ImportacionDetalle::create([
                    'importacion_id' => $importacion->id,
                    'producto_id' => $validated['producto_id'],
                    'cajas_facturadas' => $validated['cajas_facturadas'],
                ]);

                return $importacion;
            });

            return redirect()->route('importaciones.index')
                ->with('success', "Importación #$importacion->id registrada correctamente (Estado: En recepción).");
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al registrar la importación. Intente nuevamente.');
        }
    }

    public function show(Importacion $importacion)
    {
        $importacion->load('proveedor', 'detalles.producto.categoria');
        return view('importaciones.show', compact('importacion'));
    }
}
