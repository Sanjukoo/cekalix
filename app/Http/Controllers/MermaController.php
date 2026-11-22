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

        $imagen = $validated['imagen'] ?? null;
        unset($validated['imagen']);

        $resultado = DB::transaction(function () use ($validated, $imagen) {
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

            if ($imagen) {
                $validated['imagen'] = $imagen->store('mermas', 'local');
            }

            $merma = Merma::create($validated);

            return [
                'merma' => $merma->setRelation('detalleImportacion', $detalle),
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
            ->with('success', "Merma #{$resultado['merma']->id} registrada correctamente.")
            ->with('detalle', $this->detalle($resultado['merma'], $resultado['cajasEstables']));
    }

    // Mostrar la fotografía de evidencia de una merma.
    public function imagen(Merma $merma)
    {
        return $this->responderArchivo($merma->imagen);
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

        $imagen = $validated['imagen'] ?? null;
        unset($validated['imagen']);

        $imagenAnterior = null;

        $resultado = DB::transaction(function () use (
            $validated,
            $merma,
            $imagen,
            &$imagenAnterior
        ) {
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

            // Si se seleccionó una nueva fotografía, reemplaza la anterior.
            if ($imagen) {
                $imagenAnterior = $mermaActual->imagen;
                $validated['imagen'] = $imagen->store('mermas', 'local');
            }

            $mermaActual->update($validated);

            return [
                'merma' => $mermaActual->setRelation('detalleImportacion', $detalle),
                'cambios' => array_keys($mermaActual->getChanges()),
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

        // Eliminar la fotografía anterior después de actualizar correctamente.
        $this->eliminarArchivo($imagenAnterior);

        return redirect()
            ->route('mermas.index')
            ->with('success', "Merma #{$merma->id} actualizada correctamente.")
            ->with('detalle', $this->detalle($resultado['merma'], $resultado['cajasEstables']) + [
                'Campos modificados' => $this->camposModificados($resultado['cambios'], [
                    'importacion_detalle_id' => 'SKU / importación',
                    'cantidad' => 'Cajas dañadas',
                    'motivo' => 'Motivo',
                    'descripcion' => 'Descripción',
                    'fecha_registro' => 'Fecha',
                    'imagen' => 'Fotografía',
                ]),
            ]);
    }

    // Eliminar una merma existente.
    public function destroy(Merma $merma)
    {
        $imagenAnterior = null;

        $resultado = DB::transaction(function () use ($merma, &$imagenAnterior) {
            $mermaActual = Merma::whereKey($merma->id)
                ->lockForUpdate()
                ->firstOrFail();

            $detalle = $this->bloquearDetalle($mermaActual->importacion_detalle_id);

            if ($detalle->importacion->estado !== 'En recepción') {
                return [
                    'error' => 'No se puede eliminar una merma de una importación cerrada.',
                ];
            }

            $imagenAnterior = $mermaActual->imagen;

            $mermaActual->delete();

            $totalDanadas = $detalle->mermas()->sum('cantidad');

            return [
                'merma' => $mermaActual->setRelation('detalleImportacion', $detalle),
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

        // Eliminar la fotografía asociada al registro eliminado.
        $this->eliminarArchivo($imagenAnterior);

        return redirect()
            ->route('mermas.index')
            ->with('success', "Merma #{$merma->id} eliminada correctamente.")
            ->with('detalle', $this->detalle($resultado['merma'], $resultado['cajasEstables']));
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
            'imagen' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ], [
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe estar en formato JPG, JPEG, PNG o WEBP.',
            'imagen.max' => 'La imagen no debe superar los 2 MB.',
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

    // Bloquea la importación antes que el detalle.
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

    // Datos de la merma que se muestran como comprobación.
    private function detalle(Merma $merma, int $cajasEstables): array
    {
        $detalle = $merma->detalleImportacion;

        return [
            'Importación' => '#'.$detalle->importacion_id,
            'SKU' => $detalle->producto_codigo,
            'Cajas dañadas' => $merma->cantidad,
            'Motivo' => $merma->motivo === 'OTRO' ? ($merma->descripcion ?? 'Otro') : $merma->motivo,
            'Fecha' => $this->fechaCorta($merma->fecha_registro),
            'Fotografía' => $merma->imagen ? 'Adjunta' : 'Sin fotografía',
            'Cajas estables del SKU' => $cajasEstables,
        ];
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
