<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    // T1 / T2: la ruta de login responde y muestra el formulario
    public function test_la_pantalla_de_login_se_muestra(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Correo')
            ->assertSee('Contraseña')
            ->assertSee('Iniciar sesión');
    }

    // T3: un usuario no logueado es redirigido al login
    public function test_invitado_es_redirigido_al_login(): void
    {
        foreach (['/dashboard', '/productos', '/proveedores', '/importaciones'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    // T5: tras iniciar sesión va al Dashboard
    public function test_login_correcto_redirige_al_dashboard(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    // T4: con datos incorrectos el error sale en español
    public function test_login_incorrecto_muestra_error_en_espanol(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'incorrecta'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Credenciales incorrectas.']);

        $this->assertGuest();
    }

    // Tras 5 intentos fallidos se bloquea con un mensaje en el formulario (no una página 429)
    public function test_demasiados_intentos_muestra_mensaje_en_espanol(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'incorrecta']);
        }

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertStringStartsWith('Demasiados intentos', session('errors')->first('email'));
        $this->assertGuest();
    }

    // Con reCAPTCHA activo el formulario muestra la casilla "No soy un robot"
    public function test_login_muestra_recaptcha_si_esta_configurado(): void
    {
        $this->activarRecaptcha();

        $this->get('/login')
            ->assertOk()
            ->assertSee('g-recaptcha', false)
            ->assertSee('clave-sitio', false);
    }

    // Sin marcar la casilla no se puede iniciar sesión
    public function test_login_sin_recaptcha_es_rechazado(): void
    {
        $this->activarRecaptcha();
        $user = User::factory()->create(['password' => 'password']);

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('recaptcha');

        $this->assertGuest();
        Http::assertNothingSent();
    }

    // Si Google rechaza el token no se inicia sesión
    public function test_login_con_recaptcha_invalido_es_rechazado(): void
    {
        $this->activarRecaptcha(false);
        $user = User::factory()->create(['password' => 'password']);

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'password', 'g-recaptcha-response' => 'token'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('recaptcha');

        $this->assertGuest();
    }

    // Con el reCAPTCHA aprobado por Google se inicia sesión
    public function test_login_con_recaptcha_valido_redirige_al_dashboard(): void
    {
        $this->activarRecaptcha();
        $user = User::factory()->create(['password' => 'password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'g-recaptcha-response' => 'token'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        Http::assertSent(fn ($request) => $request['secret'] === 'clave-secreta' && $request['response'] === 'token');
    }

    private function activarRecaptcha(bool $respuestaGoogle = true): void
    {
        config([
            'services.recaptcha.site_key' => 'clave-sitio',
            'services.recaptcha.secret_key' => 'clave-secreta',
        ]);

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => $respuestaGoogle]),
        ]);
    }

    // T1: logout responde y cierra la sesión
    public function test_logout_cierra_la_sesion(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
