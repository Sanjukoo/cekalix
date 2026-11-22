<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller
{
    /*
     * Discos donde pueden estar los archivos adjuntos (facturas y fotografías).
     * Los nuevos se guardan en "local" (privado); "public" se conserva para los
     * archivos subidos antes de que se sirvieran mediante rutas protegidas.
     */
    private const DISCOS_ADJUNTOS = ['local', 'public'];

    // Muestra en el navegador (o descarga) un archivo adjunto guardado en el servidor.
    protected function responderArchivo(?string $ruta, ?string $nombreDescarga = null): Response
    {
        foreach (self::DISCOS_ADJUNTOS as $nombreDisco) {
            $disco = Storage::disk($nombreDisco);

            if (!$ruta || !$disco->exists($ruta)) {
                continue;
            }

            if ($nombreDescarga) {
                return $disco->download($ruta, $nombreDescarga);
            }

            return response()->file($disco->path($ruta), [
                'Content-Type' => $disco->mimeType($ruta) ?: 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        abort(404, 'No se encontró el archivo solicitado.');
    }

    // Elimina un archivo adjunto del disco donde se encuentre.
    protected function eliminarArchivo(?string $ruta): void
    {
        if (!$ruta) {
            return;
        }

        foreach (self::DISCOS_ADJUNTOS as $nombreDisco) {
            Storage::disk($nombreDisco)->delete($ruta);
        }
    }

    /*
     * Mensajes de comprobación tras guardar, editar o eliminar.
     * El layout muestra session('detalle') debajo del mensaje de éxito.
     */

    // Nombres legibles de los campos que cambiaron en un modelo actualizado.
    protected function camposModificados(array $cambios, array $etiquetas): string
    {
        $nombres = collect($cambios)
            ->map(fn ($campo) => $etiquetas[$campo] ?? null)
            ->filter()
            ->unique()
            ->values();

        return $nombres->isEmpty() ? 'Ninguno (sin cambios)' : $nombres->implode(', ');
    }

    protected function soles(mixed $monto): string
    {
        return 'S/ '.number_format((float) $monto, 2);
    }

    protected function fechaCorta(mixed $fecha): ?string
    {
        return $fecha ? \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') : null;
    }
}
