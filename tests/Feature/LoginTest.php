<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
