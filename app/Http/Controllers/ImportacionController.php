<?php

namespace App\Http\Controllers;

use App\Models\Importacion;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\ImportacionDetalle;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $proveedores = Proveedor::activos()
            ->orderBy('razon_social')
            ->get();

        $productos = Producto::orderBy('codigo')->get();
 
        $codigosPendientes = ImportacionDetalle::whereNull('producto_id')
            ->whereNotNull('producto_codigo')
            ->select('producto_codigo')
            ->distinct()
            ->orderBy('producto_codigo')
            ->pluck('producto_codigo');

        return view('importaciones.create', compact(
            'proveedores',
            'productos',
            'codigosPendientes'
      ));
    }
 
    public function store(Request $request)
   {
        $validated = $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'numero_factura' => 'required|string|max:50',
            'numero_contenedor' => 'required|string|max:50',
            'fecha_llegada' => 'required|date',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_codigo' => 'required|string|max:50',
            'detalles.*.cajas_facturadas' => 'required|integer|min:1',
        ], [
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'numero_factura.required' => 'El número de factura es obligatorio.',
            'numero_contenedor.required' => 'El número de contenedor es obligatorio.',
            'fecha_llegada.required' => 'La fecha de llegada es obligatoria.',
            'fecha_llegada.date' => 'La fecha debe ser válida.',
            'detalles.required' => 'Debe agregar al menos un producto.',
            'detalles.min' => 'Debe agregar al menos un producto.',
            'detalles.*.producto_codigo.required' => 'El SKU es obligatorio.',
            'detalles.*.producto_codigo.max' => 'El SKU no puede superar los 50 caracteres.',
            'detalles.*.cajas_facturadas.required' => 'Debe ingresar las cajas facturadas.',
            'detalles.*.cajas_facturadas.integer' => 'Las cajas deben ser un número entero.',
            'detalles.*.cajas_facturadas.min' => 'Debe ingresar al menos una caja.',
       ]);

        // Evitar que un mismo SKU se repita dentro de esta importación.
        $codigos = array_map(
           fn ($detalle) => strtoupper(trim($detalle['producto_codigo'])),
            $validated['detalles']
       );

        if (count($codigos) !== count(array_unique($codigos))) {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'detalles' => 'No repitas el mismo SKU en una importación. Agrupa sus cajas en una sola fila.',
               ]);
       }

        try {
            $importacion = DB::transaction(function () use ($validated) {
                $importacion = Importacion::create([
                    'proveedor_id' => $validated['proveedor_id'],
                    'numero_factura' => $validated['numero_factura'],
                    'numero_contenedor' => $validated['numero_contenedor'],
                    'fecha_llegada' => $validated['fecha_llegada'],
                    'estado' => 'En recepción',
               ]);

                foreach ($validated['detalles'] as $detalle) {
                    $codigo = trim($detalle['producto_codigo']);

                   // Si el producto existe, lo vinculamos.
                   // Si no existe, el SKU queda pendiente de registro.
                    $producto = Producto::where('codigo', $codigo)->first();

                    ImportacionDetalle::create([
                        'importacion_id' => $importacion->id,
                        'producto_id' => $producto ? $producto->id : null,
                        'producto_codigo' => $codigo,
                        'cajas_facturadas' => $detalle['cajas_facturadas'],
                    ]);
               }

               return $importacion;
            });

           return redirect()->route('importaciones.index')
                ->with(
                    'success',
                    "Importación #{$importacion->id} registrada correctamente (Estado: En recepción)."
               );
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with(
                    'error',
                    'Error al registrar la importación. Intente nuevamente.'
                );
        }
   }

    public function show(Importacion $importacion)
    {
        $importacion->load('proveedor', 'detalles.producto.categoria', 'detalles.conteoFisico', 'detalles.mermas');
        return view('importaciones.show', compact('importacion'));
    }

    // Cierra la recepción: lo que llegó en buen estado pasa al stock.
    public function cerrar(Importacion $importacion)
    {
        DB::transaction(function () use ($importacion) {
            $importacion = Importacion::whereKey($importacion->id)->lockForUpdate()->firstOrFail();

            if ($importacion->estado !== 'En recepción') {
                throw ValidationException::withMessages([
                    'cierre' => 'Esta importación ya fue cerrada.',
                ]);
            }

            $detalles = $importacion->detalles()
                ->with('conteoFisico')
                ->withSum('mermas', 'cantidad')
                ->lockForUpdate()
                ->get();

            foreach ($detalles as $detalle) {
                if (!$detalle->conteoFisico) {
                    throw ValidationException::withMessages([
                        'cierre' => 'Registra el conteo físico de todos los SKU antes de cerrar la recepción.',
                    ]);
                }

                if (!$detalle->producto_id) {
                    throw ValidationException::withMessages([
                        'cierre' => "El SKU {$detalle->producto_codigo} aún no está registrado como producto en Inventario.",
                    ]);
                }
            }

            foreach ($detalles as $detalle) {
                // Cajas en buen estado = contadas - dañadas; el stock se lleva en unidades.
                $cajasBuenas = $detalle->conteoFisico->cajas_contadas - (int) $detalle->mermas_sum_cantidad;

                $producto = Producto::whereKey($detalle->producto_id)->lockForUpdate()->firstOrFail();

                MovimientoInventario::registrar(
                    $producto,
                    max(0, $cajasBuenas) * $producto->unidades_por_caja,
                    'cierre_importacion',
                    "Recepción de importación #{$importacion->id}",
                    $importacion
                );
            }

            $importacion->update(['estado' => 'Procesada']);
        });

        return redirect()->route('importaciones.show', $importacion)
            ->with('success', 'Recepción cerrada. El stock de los productos fue actualizado.');
    }
}
