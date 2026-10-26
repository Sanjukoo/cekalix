<?php

namespace App\Providers;

use App\Actions\Fortify\ValidarRecaptcha;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Después de iniciar sesión se va siempre al Dashboard
        $this->app->instance(LoginResponse::class, new class implements LoginResponse {
            public function toResponse($request)
            {
                return redirect()->route('dashboard')->with('success', 'Inicio de sesión exitoso.');
            }
        });

        // Después de cerrar sesión se vuelve al login
        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse {
            public function toResponse($request)
            {
                return redirect()->route('login')->with('success', 'Sesión cerrada correctamente.');
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::loginView(fn () => view('auth.login'));

        // Mismo flujo de Fortify, pero con el reCAPTCHA antes de revisar las credenciales
        Fortify::authenticateThrough(fn () => [
            config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
            ValidarRecaptcha::class,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]);
    }
}
