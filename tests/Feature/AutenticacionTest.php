<?php

// Modelo del usuario.
use App\Models\User;
// Notificación de Laravel con el enlace de recuperación.
use Illuminate\Auth\Notifications\ResetPassword;
// Comparación de contraseñas contra el hash.
use Illuminate\Support\Facades\Hash;
// Simulador de notificaciones.
use Illuminate\Support\Facades\Notification;
// Broker de contraseñas.
use Illuminate\Support\Facades\Password;

// ---------- 18.4 PRUEBAS DE AUTENTICACIÓN (interfaz web) ----------

// Usuario no autorizado: sin sesión no se entra al panel.
test('un visitante sin sesión es enviado al login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

// La pantalla de acceso responde y está en español.
test('la pantalla de login se muestra', function () {
    $this->get(route('login'))->assertOk()->assertSee('Iniciar sesión');
});

// Datos válidos: credenciales correctas inician sesión.
test('un usuario inicia sesión con credenciales correctas', function () {
    $usuario = User::factory()->cajero()->create(['password' => 'Clave1234']);

    $this->post(route('login'), ['email' => $usuario->email, 'password' => 'Clave1234'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($usuario);
});

// Datos inválidos: contraseña incorrecta.
test('el login falla con contraseña incorrecta', function () {
    $usuario = User::factory()->create();

    $this->post(route('login'), ['email' => $usuario->email, 'password' => 'incorrecta'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

// Campos vacíos: el formulario exige correo y contraseña.
test('el login con campos vacíos muestra errores de validación', function () {
    $this->post(route('login'), ['email' => '', 'password' => ''])
        ->assertSessionHasErrors(['email', 'password']);

    $this->assertGuest();
});

// Protección contra fuerza bruta: el sexto intento se bloquea.
test('el login se bloquea después de cinco intentos fallidos', function () {
    $usuario = User::factory()->create();

    // Cinco intentos fallidos.
    foreach (range(1, 5) as $intento) {
        $this->post(route('login'), ['email' => $usuario->email, 'password' => 'mala'.$intento]);
    }

    // Incluso con la contraseña correcta queda bloqueado temporalmente.
    $this->post(route('login'), ['email' => $usuario->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

// Cierre de sesión.
test('un usuario cierra sesión', function () {
    $usuario = User::factory()->create();

    $this->actingAs($usuario)->post(route('logout'))->assertRedirect(route('login'));

    $this->assertGuest();
});

// No hay auto-registro: las cuentas las crea el administrador.
test('el registro público está deshabilitado; las cuentas las crea el administrador', function () {
    $this->get('/registro')->assertNotFound();
    $this->post('/registro', [
        'name' => 'Usuario externo',
        'email' => 'externo@example.com',
        'password' => 'Clave1234',
        'password_confirmation' => 'Clave1234',
    ])->assertNotFound();
});

// Recuperación: se envía la notificación con el enlace.
test('se envía el enlace de recuperación por correo', function () {
    Notification::fake();
    $usuario = User::factory()->create();

    $this->post(route('password.email'), ['email' => $usuario->email])->assertSessionHas('status');

    Notification::assertSentTo($usuario, ResetPassword::class);
});

// Recuperación: con token válido se cambia la contraseña.
test('con un token válido se restablece la contraseña', function () {
    $usuario = User::factory()->create(['password' => 'ClaveVieja1']);
    $token = Password::broker()->createToken($usuario);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $usuario->email,
        'password' => 'ClaveNueva1',
        'password_confirmation' => 'ClaveNueva1',
    ])->assertRedirect(route('login'));

    expect(Hash::check('ClaveNueva1', $usuario->fresh()->password))->toBeTrue();
});

// Recuperación: un token falso no sirve.
test('un token inválido no restablece la contraseña', function () {
    $usuario = User::factory()->create();

    $this->post(route('password.update'), [
        'token' => 'token-falso',
        'email' => $usuario->email,
        'password' => 'ClaveNueva1',
        'password_confirmation' => 'ClaveNueva1',
    ])->assertSessionHasErrors('email');
});

// Cambio de contraseña con sesión iniciada.
test('un usuario autenticado cambia su contraseña', function () {
    $usuario = User::factory()->create(['password' => 'ClaveVieja1']);

    $this->actingAs($usuario)->put(route('cuenta.password.update'), [
        'password_actual' => 'ClaveVieja1',
        'password' => 'ClaveNueva1',
        'password_confirmation' => 'ClaveNueva1',
    ])->assertRedirect(route('dashboard'));

    expect(Hash::check('ClaveNueva1', $usuario->fresh()->password))->toBeTrue();
});

// Cambio de contraseña: exige la actual correcta.
test('el cambio de contraseña exige la contraseña actual correcta', function () {
    $usuario = User::factory()->create(['password' => 'ClaveVieja1']);

    $this->actingAs($usuario)->put(route('cuenta.password.update'), [
        'password_actual' => 'incorrecta',
        'password' => 'ClaveNueva1',
        'password_confirmation' => 'ClaveNueva1',
    ])->assertSessionHasErrors('password_actual');
});

// Cambio de contraseña: la nueva debe cumplir la política (letras y números, mínimo 8).
test('la nueva contraseña debe tener letras, números y al menos 8 caracteres', function () {
    $usuario = User::factory()->create(['password' => 'ClaveVieja1']);

    $this->actingAs($usuario)->put(route('cuenta.password.update'), [
        'password_actual' => 'ClaveVieja1',
        'password' => 'corta',
        'password_confirmation' => 'corta',
    ])->assertSessionHasErrors('password');
});
