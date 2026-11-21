<?php

namespace App\Http\Controllers;

use App\Models\ConteoFisico;
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

            'imagenes' => ['nullable', 'array'],
            'imagenes.*' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ], [
            'conteos.required' => 'Ingresa el conteo físico de los SKU de esta importación.',
            'conteos.array' => 'El formato de los conteos no es válido.',
            'conteos.*.required' => 'Ingresa las cajas contadas para este SKU; usa 0 si no llegó ninguna.',
            'conteos.*.integer' => 'Las cajas contadas deben ser un número entero.',
            'conteos.*.min' => 'Las cajas contadas no pueden ser negativas.',
            'conteos.*.max' => 'La cantidad de cajas supera el máximo permitido.',

            'imagenes.*.image' => 'Cada archivo debe ser una imagen.',
            'imagenes.*.mimes' => 'Las imágenes deben estar en formato JPG, JPEG, PNG o WEBP.',
            'imagenes.*.max' => 'Cada imagen no debe superar los 2 MB.',
        ]);

        $imagenes = $request->file('imagenes', []);

        // Guardar las rutas anteriores para eliminarlas después de actualizar.
        $imagenesAnteriores = [];

        DB::transaction(function () use (
            $request,
            $validated,
            $importacion,
            $imagenes,
            &$imagenesAnteriores
        ) {
            $importacion = Importacion::whereKey($importacion->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($importacion->estado !== 'En recepción') {
                throw ValidationException::withMessages([
                    'conteos' => 'Solo se puede registrar el conteo de una importación en recepción.',
                ]);
            }

            $detalles = $importacion->detalles()
                ->withSum('mermas', 'cantidad')
                ->lockForUpdate()
                ->get();

            $idsRecibidos = array_keys($validated['conteos']);
            $idsEsperados = $detalles->modelKeys();

            // Cada conteo debe corresponder exactamente a los SKU de la importación.
            if (
                array_diff($idsRecibidos, $idsEsperados) ||
                array_diff($idsEsperados, $idsRecibidos)
            ) {
                throw ValidationException::withMessages([
                    'conteos' => 'Registra todos los SKU de esta importación y únicamente los que aparecen en su detalle.',
                ]);
            }

            // Verificar que las fotografías correspondan a SKU de esta importación.
            if (array_diff(array_keys($imagenes), $idsEsperados)) {
                throw ValidationException::withMessages([
                    'imagenes' => 'Una o más fotografías no corresponden a los SKU de esta importación.',
                ]);
            }

            // Las mermas salen de las cajas recibidas.
            foreach ($detalles as $detalle) {
                $danadas = (int) $detalle->mermas_sum_cantidad;

                if ((int) $validated['conteos'][$detalle->id] < $danadas) {
                    throw ValidationException::withMessages([
                        'conteos.'.$detalle->id => "Este SKU ya tiene {$danadas} cajas dañadas registradas; el conteo no puede ser menor.",
                    ]);
                }
            }

            foreach ($detalles as $detalle) {
                $contadas = (int) $validated['conteos'][$detalle->id];

                $datos = [
                    'cajas_contadas' => $contadas,
                    'diferencia' => $contadas - $detalle->cajas_facturadas,
                    'registrado_por' => $request->user()->id,
                ];

                // Buscar si ya existe un conteo para este SKU.
                $conteoExistente = $detalle->conteoFisico;

                // Si se adjunta una nueva fotografía, reemplazar la anterior.
                if (isset($imagenes[$detalle->id])) {
                    if ($conteoExistente && $conteoExistente->imagen) {
                        $imagenesAnteriores[] = $conteoExistente->imagen;
                    }

                    $datos['imagen'] = $imagenes[$detalle->id]
                        ->store('conteos_fisicos', 'local');
                }

                // Si no se adjunta una nueva imagen, conserva la anterior.
                $detalle->conteoFisico()->updateOrCreate([], $datos);
            }

            $importacion->touch();
        });

        // Eliminar las fotografías anteriores después de guardar correctamente.
        foreach ($imagenesAnteriores as $ruta) {
            $this->eliminarArchivo($ruta);
        }

        return redirect()
            ->route('importaciones.show', $importacion)
            ->with('success', 'Conteo físico guardado correctamente.');
    }

    // Mostrar la fotografía de evidencia del conteo físico de un SKU.
    public function imagen(ConteoFisico $conteo)
    {
        return $this->responderArchivo($conteo->imagen);
    }
}
