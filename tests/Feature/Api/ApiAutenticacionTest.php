<?php

// Modelo del usuario.
use App\Models\User;

// ---------- PRUEBAS DE AUTENTICACIÓN DE LA API (Laravel Sanctum) ----------

// Datos válidos: se emite un token con las habilidades del rol.
test('el login de la API entrega un token con las habilidades del rol', function () {
    $cajero = User::factory()->cajero()->create(['password' => 'Clave1234']);

    $this->postJson('/api/v1/auth/login', ['email' => $cajero->email, 'password' => 'Clave1234', 'dispositivo' => 'Pruebas'])
        ->assertCreated()
        ->assertJsonPath('tipo', 'Bearer')
        ->assertJsonPath('habilidades', ['leer', 'escribir'])
        ->assertJsonPath('usuario.rol', 'cajero')
        ->assertJsonStructure(['token', 'expira_en']);

    $this->assertDatabaseCount('personal_access_tokens', 1);
});

// El token del usuario de consulta solo permite leer.
test('el token del usuario de consulta solo tiene la habilidad leer', function () {
    $consulta = User::factory()->create(['password' => 'Clave1234']);

    $this->postJson('/api/v1/auth/login', ['email' => $consulta->email, 'password' => 'Clave1234'])
        ->assertCreated()
        ->assertJsonPath('habilidades', ['leer']);
});

// Datos inválidos: contraseña incorrecta → 401.
test('el login de la API rechaza credenciales incorrectas con 401', function () {
    $usuario = User::factory()->create();

    $this->postJson('/api/v1/auth/login', ['email' => $usuario->email, 'password' => 'incorrecta'])
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Las credenciales no coinciden con nuestros registros.');
});

// Campos vacíos → 422 con detalle por campo.
test('el login de la API con campos vacíos responde 422', function () {
    $this->postJson('/api/v1/auth/login', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

// Usuario no autenticado → 401 en español.
test('una ruta protegida sin token responde 401 en español', function () {
    $this->getJson('/api/v1/clientes')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'No autenticado. Envía un token válido en el encabezado Authorization: Bearer {token}.');
});

// Perfil del dueño del token.
test('el endpoint yo devuelve el usuario y las habilidades del token', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('pruebas', $admin->habilidadesApi(), now()->addHour())->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/auth/yo')
        ->assertOk()
        ->assertJsonPath('usuario.email', $admin->email)
        ->assertJsonPath('habilidades', ['leer', 'escribir', 'eliminar']);
});

// Logout revoca el token: ya no sirve.
test('el logout de la API revoca el token', function () {
    $usuario = User::factory()->cajero()->create();
    $token = $usuario->createToken('pruebas', $usuario->habilidadesApi())->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
    $this->assertDatabaseCount('personal_access_tokens', 0);

    // Se limpia la caché del guard para simular una solicitud nueva.
    $this->app['auth']->forgetGuards();

    $this->withToken($token)->getJson('/api/v1/auth/yo')->assertUnauthorized();
});

// Un token vencido ya no autentica.
test('un token vencido responde 401', function () {
    $usuario = User::factory()->create();
    $token = $usuario->createToken('vencido', ['leer'], now()->subMinute())->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/clientes')->assertUnauthorized();
});

// Las habilidades del token corresponden al rol (prueba directa del modelo).
test('las habilidades de la API dependen del rol', function () {
    expect(User::factory()->admin()->make()->habilidadesApi())->toBe(['leer', 'escribir', 'eliminar']);
    expect(User::factory()->cajero()->make()->habilidadesApi())->toBe(['leer', 'escribir']);
    expect(User::factory()->make()->habilidadesApi())->toBe(['leer']);
});
