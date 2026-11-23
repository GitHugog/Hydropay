<?php

// Modelos.
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\User;
// Ayudante de Sanctum para autenticar en pruebas con habilidades específicas.
use Laravel\Sanctum\Sanctum;

// ---------- PRUEBAS DE LA API DE CLIENTES (CRUD, validación, autorización y errores) ----------

// Consulta autorizada.
test('un token de consulta lista clientes paginados', function () {
    Cliente::factory()->count(3)->create();
    Sanctum::actingAs(User::factory()->create(), ['leer']);

    $this->getJson('/api/v1/clientes')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => ['*' => ['id', 'numero_cuenta', 'nombre', 'activo', 'recibos']], 'links', 'meta']);
});

// Búsqueda por texto.
test('la API filtra clientes por texto', function () {
    Cliente::factory()->create(['nombre' => 'Rosa Buscada']);
    Cliente::factory()->count(2)->create();
    Sanctum::actingAs(User::factory()->create(), ['leer']);

    $this->getJson('/api/v1/clientes?busqueda=Buscada')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nombre', 'Rosa Buscada');
});

// Usuario no autorizado por habilidad: token sin "escribir" → 403.
test('un token sin la habilidad escribir no puede registrar clientes', function () {
    Sanctum::actingAs(User::factory()->create(), ['leer']);

    $this->postJson('/api/v1/clientes', ['nombre' => 'X', 'numero_cuenta' => 'HP-1', 'direccion' => 'Calle'])
        ->assertForbidden()
        ->assertJsonPath('message', 'Tu token no tiene la habilidad necesaria para esta operación.');

    expect(Cliente::count())->toBe(0);
});

// Defensa en profundidad: aunque el token tuviera "escribir", la Policy revisa el rol real.
test('la Policy bloquea al rol consulta aunque el token tenga la habilidad escribir', function () {
    Sanctum::actingAs(User::factory()->create(), ['leer', 'escribir']);

    $this->postJson('/api/v1/clientes', ['nombre' => 'X', 'numero_cuenta' => 'HP-1', 'direccion' => 'Calle'])
        ->assertForbidden()
        ->assertJsonPath('message', 'Tu rol solo permite consultar información.');
});

// Datos válidos: alta.
test('el cajero registra un cliente por la API', function () {
    Sanctum::actingAs(User::factory()->cajero()->create(), ['leer', 'escribir']);

    $this->postJson('/api/v1/clientes', [
        'nombre' => 'Cliente API', 'numero_cuenta' => 'HP-API-1', 'direccion' => 'Calle 10', 'email' => 'api@example.com',
    ])
        ->assertCreated()
        ->assertJsonPath('data.numero_cuenta', 'HP-API-1')
        ->assertJsonPath('data.activo', true);

    $this->assertDatabaseHas('clientes', ['numero_cuenta' => 'HP-API-1', 'activo' => true]);
});

// Campos vacíos → 422.
test('el alta por API con campos vacíos responde 422 con los errores', function () {
    Sanctum::actingAs(User::factory()->cajero()->create(), ['leer', 'escribir']);

    $this->postJson('/api/v1/clientes', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nombre', 'numero_cuenta', 'direccion']);
});

// Datos inválidos → 422.
test('el alta por API rechaza correo inválido y cuenta duplicada', function () {
    Cliente::factory()->create(['numero_cuenta' => 'HP-DUP']);
    Sanctum::actingAs(User::factory()->cajero()->create(), ['leer', 'escribir']);

    $this->postJson('/api/v1/clientes', ['nombre' => 'X', 'numero_cuenta' => 'HP-DUP', 'direccion' => 'Calle', 'email' => 'malo'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['numero_cuenta', 'email']);
});

// Consulta y edición.
test('la API muestra y actualiza un cliente', function () {
    $cliente = Cliente::factory()->create(['nombre' => 'Antes']);
    Sanctum::actingAs(User::factory()->cajero()->create(), ['leer', 'escribir']);

    $this->getJson("/api/v1/clientes/{$cliente->id}")->assertOk()->assertJsonPath('data.nombre', 'Antes');

    $this->putJson("/api/v1/clientes/{$cliente->id}", [
        'nombre' => 'Después', 'numero_cuenta' => $cliente->numero_cuenta, 'direccion' => $cliente->direccion,
    ])->assertOk()->assertJsonPath('data.nombre', 'Después');

    expect($cliente->fresh()->nombre)->toBe('Después');
});

// Recurso inexistente → 404 en español.
test('un cliente inexistente responde 404 en español', function () {
    Sanctum::actingAs(User::factory()->create(), ['leer']);

    $this->getJson('/api/v1/clientes/999999')
        ->assertNotFound()
        ->assertJsonPath('message', 'El recurso solicitado no existe.');
});

// Eliminación: el cajero no tiene la habilidad "eliminar".
test('el cajero no puede eliminar clientes por la API', function () {
    $cliente = Cliente::factory()->create();
    Sanctum::actingAs(User::factory()->cajero()->create(), ['leer', 'escribir']);

    $this->deleteJson("/api/v1/clientes/{$cliente->id}")->assertForbidden();
    $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
});

// Eliminación por administrador: 409 con recibos, 200 sin recibos.
test('el administrador elimina por la API solo clientes sin recibos', function () {
    $conRecibos = Cobro::factory()->create()->cliente;
    $sinRecibos = Cliente::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['leer', 'escribir', 'eliminar']);

    $this->deleteJson("/api/v1/clientes/{$conRecibos->id}")->assertStatus(409);
    $this->deleteJson("/api/v1/clientes/{$sinRecibos->id}")->assertOk();

    $this->assertDatabaseHas('clientes', ['id' => $conRecibos->id]);
    $this->assertDatabaseMissing('clientes', ['id' => $sinRecibos->id]);
});

// Relaciones: estado de cuenta con recibos por pagar y total adeudado.
test('el estado de cuenta suma los recibos por pagar del cliente', function () {
    $cliente = Cliente::factory()->create();
    Cobro::factory()->create(['cliente_id' => $cliente->id, 'monto' => 120.50]);
    Cobro::factory()->create(['cliente_id' => $cliente->id, 'monto' => 79.50]);
    Cobro::factory()->create(['cliente_id' => $cliente->id, 'monto' => 300, 'estado' => Cobro::CANCELADO]);
    Sanctum::actingAs(User::factory()->create(), ['leer']);

    $this->getJson("/api/v1/clientes/{$cliente->id}/estado-cuenta")
        ->assertOk()
        ->assertJsonCount(2, 'data.recibos_por_pagar')
        ->assertJsonPath('data.total_adeudo', 200)
        ->assertJsonPath('data.ultimo_pago', null);
});
