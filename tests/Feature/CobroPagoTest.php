<?php

// Modelos.
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Pago;
use App\Models\User;
// Notificación de pago.
use App\Notifications\PagoRecibido;
// Archivos simulados.
use Illuminate\Http\UploadedFile;
// Simuladores de notificaciones y almacenamiento.
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

// ---------- 18.3 PRUEBAS DE VALIDACIÓN, RECIBOS Y PAGOS COMPLETOS (interfaz web) ----------

// Usuarios disponibles en cada prueba.
beforeEach(function () {
    $this->cajero = User::factory()->cajero()->create();
    $this->admin = User::factory()->admin()->create();
});

// Alta de recibo y regla "un periodo por cliente".
test('registra un recibo y evita repetir el periodo para el mismo cliente', function () {
    $cliente = Cliente::factory()->create(['activo' => true]);
    $datos = ['cliente_id' => $cliente->id, 'periodo' => 'Noviembre 2026', 'monto' => 250.50];

    $this->actingAs($this->cajero)->post(route('cobros.store'), $datos)->assertSessionHas('mensaje');
    $this->assertDatabaseHas('cobros', ['cliente_id' => $cliente->id, 'periodo' => 'Noviembre 2026']);

    $this->actingAs($this->cajero)->post(route('cobros.store'), $datos)->assertSessionHasErrors('periodo');
});

// Relación cliente → recibos: no se emiten recibos a clientes inactivos.
test('no permite emitir recibos a un cliente inactivo', function () {
    $cliente = Cliente::factory()->inactivo()->create();

    $this->actingAs($this->cajero)->post(route('cobros.store'), [
        'cliente_id' => $cliente->id, 'periodo' => 'Diciembre 2026', 'monto' => 100,
    ])->assertSessionHasErrors('cliente_id');
});

// Recibo con fecha límite pasada nace vencido.
test('un recibo con fecha límite pasada se registra como vencido', function () {
    $cliente = Cliente::factory()->create(['activo' => true]);

    $this->actingAs($this->cajero)->post(route('cobros.store'), [
        'cliente_id' => $cliente->id, 'periodo' => 'Enero 2026', 'monto' => 100,
        'fecha_vencimiento' => now()->subDays(10)->toDateString(),
    ]);

    expect(Cobro::firstOrFail()->estado)->toBe(Cobro::VENCIDO);
});

// Cancelar es exclusivo del administrador.
test('solo el administrador puede cancelar un recibo', function () {
    $cobro = Cobro::factory()->create([
        'estado' => Cobro::PENDIENTE,
        'fecha_vencimiento' => now()->addDays(10),
    ]);

    $datos = [
        'cliente_id' => $cobro->cliente_id,
        'periodo' => $cobro->periodo,
        'monto' => $cobro->monto,
        'fecha_vencimiento' => $cobro->fecha_vencimiento->format('Y-m-d'),
        'estado' => Cobro::CANCELADO,
    ];

    $this->actingAs($this->cajero)->put(route('cobros.update', $cobro), $datos)->assertForbidden();
    expect($cobro->fresh()->estado)->toBe(Cobro::PENDIENTE);

    $this->actingAs($this->admin)->put(route('cobros.update', $cobro), $datos)->assertSessionHas('mensaje');
    expect($cobro->fresh()->estado)->toBe(Cobro::CANCELADO);
});

// Datos inválidos: importe negativo y con más de dos decimales.
test('no permite registrar recibos con importe negativo o con más de dos decimales', function () {
    $cliente = Cliente::factory()->create(['activo' => true]);

    $this->actingAs($this->cajero)->post(route('cobros.store'), [
        'cliente_id' => $cliente->id, 'periodo' => 'Enero 2027', 'monto' => -5,
    ])->assertSessionHasErrors('monto');

    $this->actingAs($this->cajero)->post(route('cobros.store'), [
        'cliente_id' => $cliente->id, 'periodo' => 'Febrero 2027', 'monto' => 10.555,
    ])->assertSessionHasErrors('monto');
});

// El mensaje de la regla "decimal" está traducido (antes aparecía "validation.decimal").
test('los mensajes de validación aparecen en español y no como claves', function () {
    $cliente = Cliente::factory()->create(['activo' => true]);

    // assertInvalid revisa que el error de "monto" contenga el texto en español "decimales".
    $respuesta = $this->actingAs($this->cajero)->post(route('cobros.store'), [
        'cliente_id' => $cliente->id, 'periodo' => 'Marzo 2027', 'monto' => 10.555,
    ]);
    $respuesta->assertInvalid(['monto' => 'decimales']);
});

// Pago completo: se toma el importe del recibo y lo liquida.
test('un pago registra exactamente el importe total del recibo y lo marca pagado', function () {
    Notification::fake();
    $cobro = Cobro::factory()->create([
        'monto' => 350,
        'fecha_vencimiento' => now()->addDays(10),
        'estado' => Cobro::PENDIENTE,
    ]);

    $this->actingAs($this->cajero)->post(route('pagos.store'), [
        'cobro_id' => $cobro->id,
        'metodo' => 'efectivo',
        'fecha_pago' => now()->toDateString(),
    ])->assertSessionHas('mensaje');

    expect($cobro->fresh()->estado)->toBe(Cobro::PAGADO);
    expect($cobro->fresh()->saldo())->toBe(0.0);
    expect((float) $cobro->fresh()->pago->monto)->toBe(350.0);
    expect(Pago::where('cobro_id', $cobro->id)->count())->toBe(1);
});

// Regla de negocio: no hay abonos.
test('rechaza un intento de pago parcial', function () {
    $cobro = Cobro::factory()->create(['monto' => 350]);

    $this->actingAs($this->cajero)->post(route('pagos.store'), [
        'cobro_id' => $cobro->id,
        'monto' => 100,
        'metodo' => 'efectivo',
        'fecha_pago' => now()->toDateString(),
    ])->assertSessionHasErrors('monto');

    expect(Pago::count())->toBe(0);
    expect($cobro->fresh()->estado)->toBe(Cobro::PENDIENTE);
});

// Regla de negocio: tampoco importes mayores.
test('rechaza un importe enviado que sea mayor o diferente al recibo', function () {
    $cobro = Cobro::factory()->create(['monto' => 350]);

    $this->actingAs($this->cajero)->post(route('pagos.store'), [
        'cobro_id' => $cobro->id,
        'monto' => 400,
        'metodo' => 'efectivo',
        'fecha_pago' => now()->toDateString(),
    ])->assertSessionHasErrors('monto');

    expect(Pago::count())->toBe(0);
});

// Regla de negocio: un solo pago por recibo.
test('no permite registrar un segundo pago para el mismo recibo', function () {
    Notification::fake();
    $cobro = Cobro::factory()->create(['monto' => 250]);

    $datos = [
        'cobro_id' => $cobro->id,
        'metodo' => 'efectivo',
        'fecha_pago' => now()->toDateString(),
    ];

    $this->actingAs($this->cajero)->post(route('pagos.store'), $datos)->assertSessionHas('mensaje');
    $this->actingAs($this->cajero)->post(route('pagos.store'), $datos)->assertSessionHasErrors('cobro_id');

    expect(Pago::where('cobro_id', $cobro->id)->count())->toBe(1);
});

// Integridad en base de datos: índice UNIQUE de pagos.cobro_id.
test('la base de datos impide un segundo pago para el mismo recibo', function () {
    $pagoExistente = Pago::factory()->create();

    expect(fn () => Pago::factory()->create([
        'cobro_id' => $pagoExistente->cobro_id,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

// Un recibo cancelado no se cobra.
test('un recibo cancelado no admite pagos', function () {
    $cobro = Cobro::factory()->create([
        'monto' => 250,
        'estado' => Cobro::CANCELADO,
    ]);

    $this->actingAs($this->cajero)->post(route('pagos.store'), [
        'cobro_id' => $cobro->id,
        'metodo' => 'efectivo',
        'fecha_pago' => now()->toDateString(),
    ])->assertSessionHasErrors('cobro_id');

    expect(Pago::count())->toBe(0);
});

// Datos inválidos en pagos.
test('rechaza pagos con fecha futura o método inválido', function () {
    $cobro = Cobro::factory()->create(['monto' => 100]);

    $this->actingAs($this->cajero)->post(route('pagos.store'), [
        'cobro_id' => $cobro->id,
        'metodo' => 'bitcoin',
        'fecha_pago' => now()->addDay()->toDateString(),
    ])->assertSessionHasErrors(['metodo', 'fecha_pago']);
});

// Events + Notifications: el cliente recibe la confirmación.
test('registrar un pago dispara la notificación por correo al cliente', function () {
    Notification::fake();
    $cliente = Cliente::factory()->create(['email' => 'cliente@example.com']);
    $cobro = Cobro::factory()->create(['cliente_id' => $cliente->id, 'monto' => 100]);

    $this->actingAs($this->cajero)->post(route('pagos.store'), [
        'cobro_id' => $cobro->id,
        'metodo' => 'tarjeta',
        'fecha_pago' => now()->toDateString(),
    ]);

    Notification::assertSentOnDemand(PagoRecibido::class);
});

// File Storage: comprobante privado y tipo de archivo validado.
test('guarda el comprobante en almacenamiento privado y valida su tipo', function () {
    Notification::fake();
    Storage::fake('local');
    $cobro = Cobro::factory()->create(['monto' => 100]);

    $this->actingAs($this->cajero)->post(route('pagos.store'), [
        'cobro_id' => $cobro->id,
        'metodo' => 'deposito',
        'fecha_pago' => now()->toDateString(),
        'comprobante' => UploadedFile::fake()->create('virus.exe', 10),
    ])->assertSessionHasErrors('comprobante');

    $this->actingAs($this->cajero)->post(route('pagos.store'), [
        'cobro_id' => $cobro->id,
        'metodo' => 'deposito',
        'fecha_pago' => now()->toDateString(),
        'comprobante' => UploadedFile::fake()->create('recibo.pdf', 100, 'application/pdf'),
    ]);

    $pago = Pago::firstOrFail();
    Storage::disk('local')->assertExists($pago->comprobante);

    // La descarga funciona solo con sesión iniciada.
    $this->actingAs($this->cajero)->get(route('pagos.comprobante', $pago))->assertOk();
});

// Integridad: un recibo pagado no se elimina.
test('un recibo con pago no se puede eliminar', function () {
    $pago = Pago::factory()->create();

    $this->actingAs($this->admin)->delete(route('cobros.destroy', $pago->cobro))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('cobros', ['id' => $pago->cobro_id]);
});

// Historial financiero: no existe ruta para eliminar pagos.
test('los pagos no tienen una ruta de eliminación para conservar el historial financiero', function () {
    $pago = Pago::factory()->create();

    $this->actingAs($this->admin)->delete('/pagos/'.$pago->id)->assertStatus(405);
    $this->assertDatabaseHas('pagos', ['id' => $pago->id]);
});

// Artisan Console: comando de vencimientos.
test('el comando artisan marca como vencidos los recibos atrasados sin pago', function () {
    $atrasado = Cobro::factory()->create([
        'fecha_vencimiento' => now()->subDays(3),
        'estado' => Cobro::PENDIENTE,
    ]);
    $vigente = Cobro::factory()->create([
        'fecha_vencimiento' => now()->addDays(3),
        'estado' => Cobro::PENDIENTE,
    ]);

    $this->artisan('hydropay:marcar-vencidos')->assertSuccessful();

    expect($atrasado->fresh()->estado)->toBe(Cobro::VENCIDO);
    expect($vigente->fresh()->estado)->toBe(Cobro::PENDIENTE);
});

// Panel principal.
test('el panel principal muestra el resumen', function () {
    Cobro::factory()->count(2)->create();

    $this->actingAs($this->cajero)->get(route('dashboard'))->assertOk()->assertSee('Cobros pendientes');
});
