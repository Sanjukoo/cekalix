<?php

namespace App\Http\Controllers;

use App\Models\Merma;
use App\Models\Importacion;
use App\Models\ImportacionDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MermaController extends Controller
{
    // Mostrar el historial compartido.
    public function index()
    {
        $mermas = Merma::with(
            'detalleImportacion.importacion.proveedor',
            'detalleImportacion.producto',
            'detalleImportacion.mermas'
        )
            ->orderBy('id', 'desc')
            ->paginate(15);

        $totalCajasDaniadas = Merma::sum('cantidad');

        return view('mermas.index', compact(
            'mermas',
            'totalCajasDaniadas'
        ));
    }

    public function create()
    {
        $importaciones = Importacion::with(
            'proveedor',
            'detalles.producto',
            'detalles.mermas'
        )
            ->orderBy('id', 'desc')
            ->get();

        return view('mermas.create', compact('importaciones'));
    }

    // Registrar una nueva merma.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'importacion_detalle_id' => [
                'required',
                'exists:importacion_detalles,id',
            ],
            'cantidad' => [
                'required',
                'integer',
                'min:1',
            ],
            'motivo' => [
                'required',
                'string',
                'max:255',
            ],
            'descripcion' => [
                'nullable',
                'required_if:motivo,OTRO',
                'string',
                'max:1000',
            ],
            'fecha_registro' => [
                'required',
                'date',
            ],
        ]);

        if ($validated['motivo'] !== 'OTRO') {
            $validated['descripcion'] = null;
        }

        $resultado = DB::transaction(function () use ($validated) {
            $detalle = ImportacionDetalle::whereKey(
                $validated['importacion_detalle_id']
            )
                ->lockForUpdate()
                ->firstOrFail();

            $totalRegistrado = $detalle->mermas()->sum('cantidad');

            $totalNuevo = $totalRegistrado + $validated['cantidad'];

            if ($totalNuevo > $detalle->cajas_facturadas) {
                return [
                    'error' => 'La cantidad dañada supera las cajas facturadas de esta importación.',
                ];
            }

            Merma::create($validated);

            return [
                'cajasEstables' =>
                    $detalle->cajas_facturadas - $totalNuevo,
            ];
        });

        if (isset($resultado['error'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'cantidad' => $resultado['error'],
                ]);
        }

        return redirect()
            ->route('mermas.index')
            ->with('success', 'Merma registrada correctamente.')
            ->with('cajasEstables', $resultado['cajasEstables']);
    }

    // Mostrar el formulario de edición.
    public function edit(Merma $merma)
    {
        $merma->load('detalleImportacion');

        $importaciones = Importacion::with(
            'proveedor',
            'detalles.producto',
            'detalles.mermas'
        )
            ->orderBy('id', 'desc')
            ->get();

        return view('mermas.edit', compact(
            'merma',
            'importaciones'
        ));
    }

    // Actualizar una merma existente.
    public function update(Request $request, Merma $merma)
    {
        $validated = $request->validate([
            'importacion_detalle_id' => [
                'required',
                'exists:importacion_detalles,id',
            ],
            'cantidad' => [
                'required',
                'integer',
                'min:1',
            ],
            'motivo' => [
                'required',
                'string',
                'max:255',
            ],
            'descripcion' => [
                'nullable',
                'required_if:motivo,OTRO',
                'string',
                'max:1000',
            ],
            'fecha_registro' => [
                'required',
                'date',
            ],
        ]);

        if ($validated['motivo'] !== 'OTRO') {
            $validated['descripcion'] = null;
        }

        $resultado = DB::transaction(function () use ($validated, $merma) {
            $mermaActual = Merma::whereKey($merma->id)
                ->lockForUpdate()
                ->firstOrFail();

            $detalle = ImportacionDetalle::whereKey(
                $validated['importacion_detalle_id']
            )
                ->lockForUpdate()
                ->firstOrFail();

            // Excluir la merma actual para no contarla dos veces.
            $otrasMermas = $detalle->mermas()
                ->where('id', '!=', $mermaActual->id)
                ->sum('cantidad');

            $totalNuevo = $otrasMermas + $validated['cantidad'];

            if ($totalNuevo > $detalle->cajas_facturadas) {
                return [
                    'error' => 'La cantidad dañada supera las cajas facturadas de esta importación.',
                ];
            }

            $mermaActual->update($validated);

            return [
                'cajasEstables' =>
                    $detalle->cajas_facturadas - $totalNuevo,
            ];
        });

        if (isset($resultado['error'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'cantidad' => $resultado['error'],
                ]);
        }

        return redirect()
            ->route('mermas.index')
            ->with('success', 'Merma actualizada correctamente.')
            ->with('cajasEstables', $resultado['cajasEstables']);
    }

    // Eliminar una merma existente.
    public function destroy(Merma $merma)
    {
        $resultado = DB::transaction(function () use ($merma) {
            $mermaActual = Merma::whereKey($merma->id)
                ->lockForUpdate()
                ->firstOrFail();

            $detalle = ImportacionDetalle::whereKey(
                $mermaActual->importacion_detalle_id
            )
                ->lockForUpdate()
                ->firstOrFail();

            $mermaActual->delete();

            $totalDanadas = $detalle->mermas()->sum('cantidad');

            return max(
                0,
                $detalle->cajas_facturadas - $totalDanadas
            );
        });

        return redirect()
            ->route('mermas.index')
            ->with('success', 'Merma eliminada correctamente.')
            ->with('cajasEstables', $resultado);
    }
}
