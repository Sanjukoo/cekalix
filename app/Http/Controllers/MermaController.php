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
            'detalleImportacion.conteoFisico',
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
        $importaciones = $this->importacionesEnRecepcion();

        return view('mermas.create', compact('importaciones'));
    }

    // Registrar una nueva merma.
    public function store(Request $request)
    {
        $validated = $this->validar($request);

        $resultado = DB::transaction(function () use ($validated) {
            $detalle = $this->bloquearDetalle($validated['importacion_detalle_id']);

            if ($error = $this->errorDeDetalle($detalle)) {
                return ['error' => $error];
            }

            $totalRegistrado = $detalle->mermas()->sum('cantidad');

            $totalNuevo = $totalRegistrado + $validated['cantidad'];

            $cajasRecibidas = $detalle->conteoFisico->cajas_contadas;

            if ($totalNuevo > $cajasRecibidas) {
                return [
                    'error' => 'La cantidad dañada supera las cajas recibidas en el conteo físico de este SKU.',
                ];
            }

            Merma::create($validated);

            return [
                'cajasEstables' => $cajasRecibidas - $totalNuevo,
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
        $merma->load('detalleImportacion.importacion');

        if ($merma->detalleImportacion->importacion->estado !== 'En recepción') {
            return redirect()
                ->route('mermas.index')
                ->with('error', 'No se puede editar una merma de una importación cerrada.');
        }

        $importaciones = $this->importacionesEnRecepcion();

        return view('mermas.edit', compact(
            'merma',
            'importaciones'
        ));
    }

    // Actualizar una merma existente.
    public function update(Request $request, Merma $merma)
    {
        $validated = $this->validar($request);

        $resultado = DB::transaction(function () use ($validated, $merma) {
            $mermaActual = Merma::whereKey($merma->id)
                ->lockForUpdate()
                ->firstOrFail();

            // La importación de origen tampoco puede estar cerrada.
            $detalleAnterior = $this->bloquearDetalle($mermaActual->importacion_detalle_id);

            if ($detalleAnterior->importacion->estado !== 'En recepción') {
                return [
                    'error' => 'No se puede modificar una merma de una importación cerrada.',
                ];
            }

            $detalle = $this->bloquearDetalle($validated['importacion_detalle_id']);

            if ($error = $this->errorDeDetalle($detalle)) {
                return ['error' => $error];
            }

            // Excluir la merma actual para no contarla dos veces.
            $otrasMermas = $detalle->mermas()
                ->where('id', '!=', $mermaActual->id)
                ->sum('cantidad');

            $totalNuevo = $otrasMermas + $validated['cantidad'];

            $cajasRecibidas = $detalle->conteoFisico->cajas_contadas;

            if ($totalNuevo > $cajasRecibidas) {
                return [
                    'error' => 'La cantidad dañada supera las cajas recibidas en el conteo físico de este SKU.',
                ];
            }

            $mermaActual->update($validated);

            return [
                'cajasEstables' => $cajasRecibidas - $totalNuevo,
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

            $detalle = $this->bloquearDetalle($mermaActual->importacion_detalle_id);

            if ($detalle->importacion->estado !== 'En recepción') {
                return [
                    'error' => 'No se puede eliminar una merma de una importación cerrada.',
                ];
            }

            $mermaActual->delete();

            $totalDanadas = $detalle->mermas()->sum('cantidad');

            return [
                'cajasEstables' => max(
                    0,
                    ($detalle->conteoFisico?->cajas_contadas ?? 0) - $totalDanadas
                ),
            ];
        });

        if (isset($resultado['error'])) {
            return redirect()
                ->route('mermas.index')
                ->with('error', $resultado['error']);
        }

        return redirect()
            ->route('mermas.index')
            ->with('success', 'Merma eliminada correctamente.')
            ->with('cajasEstables', $resultado['cajasEstables']);
    }

    private function validar(Request $request): array
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

        return $validated;
    }

    // Solo se registran mermas de SKU ya contados en importaciones abiertas.
    private function importacionesEnRecepcion()
    {
        return Importacion::with(
            'proveedor',
            'detalles.producto',
            'detalles.conteoFisico',
            'detalles.mermas'
        )
            ->where('estado', 'En recepción')
            ->orderBy('id', 'desc')
            ->get();
    }

    // Bloquea la importación antes que el detalle, en el mismo orden que el conteo y el cierre.
    private function bloquearDetalle(int $detalleId): ImportacionDetalle
    {
        $importacionId = ImportacionDetalle::whereKey($detalleId)->value('importacion_id');

        $importacion = Importacion::whereKey($importacionId)
            ->lockForUpdate()
            ->firstOrFail();

        $detalle = ImportacionDetalle::with('conteoFisico')
            ->whereKey($detalleId)
            ->lockForUpdate()
            ->firstOrFail();

        return $detalle->setRelation('importacion', $importacion);
    }

    private function errorDeDetalle(ImportacionDetalle $detalle): ?string
    {
        if ($detalle->importacion->estado !== 'En recepción') {
            return 'Solo se pueden registrar mermas de importaciones en recepción.';
        }

        if (!$detalle->conteoFisico) {
            return 'Registra primero el conteo físico de este SKU.';
        }

        return null;
    }
}
