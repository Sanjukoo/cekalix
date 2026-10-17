<?php

namespace App\Http\Controllers;

use App\Models\Importacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConteoFisicoController extends Controller
{
    public function update(Request $request, Importacion $importacion)
    {
        $validated = $request->validate([
            'conteos' => ['required', 'array', 'min:1'],
            'conteos.*' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ], [
            'conteos.required' => 'Ingresa el conteo físico de los SKU de esta importación.',
            'conteos.array' => 'El formato de los conteos no es válido.',
            'conteos.*.required' => 'Ingresa las cajas contadas para este SKU; usa 0 si no llegó ninguna.',
            'conteos.*.integer' => 'Las cajas contadas deben ser un número entero.',
            'conteos.*.min' => 'Las cajas contadas no pueden ser negativas.',
            'conteos.*.max' => 'La cantidad de cajas supera el máximo permitido.',
        ]);

        DB::transaction(function () use ($request, $validated, $importacion) {
            $importacion = Importacion::whereKey($importacion->id)->lockForUpdate()->firstOrFail();

            if ($importacion->estado !== 'En recepción') {
                throw ValidationException::withMessages([
                    'conteos' => 'Solo se puede registrar el conteo de una importación en recepción.',
                ]);
            }

            $detalles = $importacion->detalles()->lockForUpdate()->get();
            $idsRecibidos = array_keys($validated['conteos']);
            $idsEsperados = $detalles->modelKeys();

            // Cada conteo debe corresponder exactamente a los SKU de la importación seleccionada.
            if (array_diff($idsRecibidos, $idsEsperados) || array_diff($idsEsperados, $idsRecibidos)) {
                throw ValidationException::withMessages([
                    'conteos' => 'Registra todos los SKU de esta importación y únicamente los que aparecen en su detalle.',
                ]);
            }

            foreach ($detalles as $detalle) {
                $contadas = (int) $validated['conteos'][$detalle->id];
                $detalle->conteoFisico()->updateOrCreate([], [
                    'cajas_contadas' => $contadas,
                    'diferencia' => $contadas - $detalle->cajas_facturadas,
                    'registrado_por' => $request->user()->id,
                ]);
            }

            $importacion->touch();
        });

        return redirect()->route('importaciones.show', $importacion)
            ->with('success', 'Conteo físico guardado correctamente.');
    }
}
