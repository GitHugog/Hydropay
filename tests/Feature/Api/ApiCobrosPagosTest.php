<?php

// Modelos.
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Pago;
use App\Models\User;
// Simulador de notificaciones.
use Illuminate\Support\Facades\Notification;
// Ayudante de Sanctum para pruebas.
use Laravel\Sanctum\Sanctum;

// ---------- PRUEBAS DE LA API DE RECIBOS, PAGOS Y RESUMEN ----------

// Cada prueba usa un cajero con token de lectura y escritura.
beforeEach(function () {
    Notification::fake();
    $this->cajero = User::factory()->cajero()->create();
    Sanctum::actingAs($this->cajero, ['leer', 'escribir']);
});

// Alta de recibo y regla de periodo único.
test('registra un recibo por la API y rechaza el periodo repetido', function () {
    $cliente = Cliente::factory()->create(['activo' => true]);
    $datos = ['cliente_id' => $cliente->id, 'periodo' => 'Diciembre 2026', 'monto' => 315.75];

    $this->postJson('/api/v1/cobros', $datos)
        ->assertCreated()
        ->assertJsonPath('data.estado', 'pendiente')
        ->assertJsonPath('data.estado_texto', 'Sin pagar')
        ->assertJsonPath('data.cliente.id', $cliente->id);

    $this->postJson('/api/v1/cobros', $datos)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('periodo');
});

// Filtro por estado.
test('la API filtra recibos por estado', function () {
    Cobro::factory()->count(2)->create();
    Cobro::factory()->vencido()->create();

    $this->getJson('/api/v1/cobros?estado=vencido')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.estado', 'vencido');
});

// Pago completo por la API.
test('registra un pago completo por la API y el recibo queda pagado', function () {
    $cobro = Cobro::factory()->create(['monto' => 410]);

    $this->postJson('/api/v1/pagos', ['cobro_id' => $cobro->id, 'metodo' => 'transferencia', 'referencia' => 'SPEI-123', 'fecha_pago' => now()->toDateString()])
        ->assertCreated()
        ->assertJsonPath('data.monto', 410)
        ->assertJsonPath('data.cobro.estado', 'pagado')
        ->assertJsonPath('data.registrado_por.id', $this->cajero->id);

    expect($cobro->fresh()->estado)->toBe(Cobro::PAGADO);
});

// Regla de negocio por API: sin abonos.
test('la API rechaza abonos con 422', function () {
    $cobro = Cobro::factory()->create(['monto' => 410]);

    $this->postJson('/api/v1/pagos', ['cobro_id' => $cobro->id, 'monto' => 100, 'metodo' => 'efectivo', 'fecha_pago' => now()->toDateString()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('monto');

    expect(Pago::count())->toBe(0);
});

// Regla de negocio por API: un solo pago.
test('la API rechaza un segundo pago del mismo recibo', function () {
    $pago = Pago::factory()->create();

    $this->postJson('/api/v1/pagos', ['cobro_id' => $pago->cobro_id, 'metodo' => 'efectivo', 'fecha_pago' => now()->toDateString()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cobro_id');
});

// Historial de pagos.
test('la API lista los pagos con su recibo y cliente', function () {
    Pago::factory()->count(2)->create();

    $this->getJson('/api/v1/pagos')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => ['*' => ['id', 'monto', 'metodo', 'cobro' => ['periodo', 'cliente' => ['nombre']]]]]);
});

// Un recibo pagado no se modifica (CobroPolicy) → 403.
test('la API no permite modificar un recibo pagado', function () {
    $pago = Pago::factory()->create();
    $cobro = $pago->cobro;

    $this->putJson("/api/v1/cobros/{$cobro->id}", [
        'cliente_id' => $cobro->cliente_id, 'periodo' => $cobro->periodo, 'monto' => 1, 'estado' => 'pendiente',
    ])->assertForbidden()
        ->assertJsonPath('message', 'Este recibo ya fue liquidado y no puede modificarse.');
});

// Eliminación de recibo pagado por administrador → 409.
test('el administrador no puede eliminar por la API un recibo pagado', function () {
    $pago = Pago::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['leer', 'escribir', 'eliminar']);

    $this->deleteJson("/api/v1/cobros/{$pago->cobro_id}")->assertStatus(409);
    $this->assertDatabaseHas('cobros', ['id' => $pago->cobro_id]);
});

// Método HTTP no permitido → 405 en español.
test('un método no permitido responde 405 en español', function () {
    $pago = Pago::factory()->create();

    $this->deleteJson("/api/v1/pagos/{$pago->id}")
        ->assertStatus(405)
        ->assertJsonPath('message', 'Método HTTP no permitido para esta ruta.');
});

// Resumen del panel por API.
test('el resumen de la API devuelve los indicadores del panel', function () {
    Cobro::factory()->count(2)->create();
    Pago::factory()->create();

    $this->getJson('/api/v1/resumen')
        ->assertOk()
        ->assertJsonStructure(['data' => ['clientes', 'pagos', 'pendientes', 'vencidos', 'cobrado', 'por_cobrar']])
        ->assertJsonPath('data.pagos', 1);
});
