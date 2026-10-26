<?php

namespace App\Actions\Fortify;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Paso del inicio de sesión que comprueba el reCAPTCHA de Google
 * ("No soy un robot") antes de revisar las credenciales.
 */
class ValidarRecaptcha
{
    public function handle(Request $request, Closure $next)
    {
        $secreto = config('services.recaptcha.secret_key');

        // Sin claves configuradas no se exige el reCAPTCHA
        if (blank($secreto)) {
            return $next($request);
        }

        $token = $request->input('g-recaptcha-response');

        if (blank($token)) {
            $this->fallar('Marca la casilla "No soy un robot" para continuar.');
        }

        try {
            $respuesta = Http::asForm()->timeout(10)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secreto,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
        } catch (ConnectionException) {
            $this->fallar('No se pudo verificar el reCAPTCHA. Inténtalo de nuevo.');
        }

        if (! $respuesta->successful() || ! $respuesta->json('success')) {
            $this->fallar('La verificación "No soy un robot" no es válida. Inténtalo de nuevo.');
        }

        return $next($request);
    }

    private function fallar(string $mensaje): never
    {
        throw ValidationException::withMessages(['recaptcha' => $mensaje]);
    }
}
