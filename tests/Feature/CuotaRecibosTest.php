<?php

// Modelos.
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\User;
// Servicio que genera los recibos.
use App\Services\GeneradorRecibosService;
// Fechas.
use Illuminate\Support\Carbon;

// ---------- CUOTA DEL SERVICIO Y RECIBOS AUTOMÁTICOS (mensual, bimestral, anual) ----------

// Antes de cada prueba: usuarios y una fecha fija (15 de noviembre de 2026) para resultados repetibles.
beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->cajero = User::factory()->cajero()->create();
    $this->travelTo(Carbon::parse('2026-11-15 10:00:00'));
});

// Cálculo del periodo según la periodicidad.
test('calcula la etiqueta y la fecha límite de cada periodicidad', function () {
    $mensual = GeneradorRecibosService::periodo(Cliente::MENSUAL, Carbon::parse('2026-11-15'));
    expect($mensual['etiqueta'])->toBe('Noviembre 2026')
        ->and($mensual['fin']->toDateString())->toBe('2026-11-30');

    $bimestral = GeneradorRecibosService::periodo(Cliente::BIMESTRAL, Carbon::parse('2026-12-03'));
    expect($bimestral['etiqueta'])->toBe('Noviembre-Diciembre 2026')
        ->and($bimestral['fin']->toDateString())->toBe('2026-12-31');

    $enero = GeneradorRecibosService::periodo(Cliente::BIMESTRAL, Carbon::parse('2027-01-20'));
    expect($enero['etiqueta'])->toBe('Enero-Febrero 2027')
        ->and($enero['fin']->toDateString())->toBe('2027-02-28');

    $anual = GeneradorRecibosService::periodo(Cliente::ANUAL, Carbon::parse('2026-11-15'));
    expect($anual['etiqueta'])->toBe('Anual 2026')
        ->and($anual['fin']->toDateString())->toBe('2026-12-31');
});

// El administrador asigna la cuota y el recibo del periodo aparece de inmediato.
test('el administrador registra un cliente con cuota y se genera su recibo del periodo', function () {
    $this->actingAs($this->admin)->post(route('clientes.store'), [
        'nombre' => 'Rosa', 'numero_cuenta' => 'HP-500', 'direccion' => 'Rancho Viejo', 'activo' => 1,
        'periodicidad' => 'mensual', 'tarifa' => '150.00',
    ])->assertSessionHas('mensaje');

    $cliente = Cliente::where('numero_cuenta', 'HP-500')->firstOrFail();

    expect($cliente->cobros)->toHaveCount(1)
        ->and($cliente->cobros->first()->periodo)->toBe('Noviembre 2026')
        ->and((float) $cliente->cobros->first()->monto)->toBe(150.0)
        ->and($cliente->cobros->first()->estado)->toBe(Cobro::PENDIENTE)
        ->and($cliente->adeudo())->toBe(150.0);

    // El listado de clientes muestra cuánto debe.
    $this->actingAs($this->cajero)->get(route('clientes.index'))->assertOk()->assertSee('$150.00');
});

// Un cliente sin cuota no recibe recibos automáticos.
test('un cliente sin cuota no genera recibos', function () {
    $this->actingAs($this->admin)->post(route('clientes.store'), [
        'nombre' => 'Pedro', 'numero_cuenta' => 'HP-501', 'direccion' => 'Centro', 'activo' => 1,
        'periodicidad' => 'mensual', 'tarifa' => '',
    ]);

    expect(Cobro::count())->toBe(0);
});

// Solo el administrador asigna la cuota.
test('el cajero no puede asignar ni cambiar la cuota', function () {
    $this->actingAs($this->cajero)->post(route('clientes.store'), [
        'nombre' => 'Luis', 'numero_cuenta' => 'HP-502', 'direccion' => 'Calle 1', 'activo' => 1,
        'periodicidad' => 'anual', 'tarifa' => '10',
    ])->assertSessionHasErrors(['periodicidad', 'tarifa']);

    expect(Cliente::count())->toBe(0);
});

// La tarea diaria no repite recibos y respeta la periodicidad.
test('el comando genera un recibo por periodo sin duplicarlo', function () {
    Cliente::factory()->conCuota(150, Cliente::MENSUAL)->create();
    Cliente::factory()->conCuota(320, Cliente::BIMESTRAL)->create();
    Cliente::factory()->conCuota(1800, Cliente::ANUAL)->create();
    Cliente::factory()->inactivo()->create(['tarifa' => 99]);
    Cliente::factory()->create();

    $this->artisan('hydropay:generar-recibos')->assertSuccessful();
    $this->artisan('hydropay:generar-recibos')->assertSuccessful();

    expect(Cobro::count())->toBe(3)
        ->and(Cobro::pluck('periodo')->sort()->values()->all())
        ->toBe(['Anual 2026', 'Noviembre 2026', 'Noviembre-Diciembre 2026']);

    // En diciembre solo el cliente mensual necesita recibo nuevo.
    $this->artisan('hydropay:generar-recibos', ['--fecha' => '2026-12-01'])->assertSuccessful();
    expect(Cobro::count())->toBe(4)
        ->and(Cobro::where('periodo', 'Diciembre 2026')->exists())->toBeTrue();
});

// Botón "Generar recibos del periodo": solo administrador.
test('solo el administrador usa el botón de generar recibos', function () {
    Cliente::factory()->conCuota(200)->create();

    $this->actingAs($this->cajero)->post(route('cobros.generar'))->assertForbidden();
    expect(Cobro::count())->toBe(0);

    $this->actingAs($this->admin)->post(route('cobros.generar'))
        ->assertRedirect(route('cobros.index'))
        ->assertSessionHas('mensaje', 'Se generó 1 recibo del periodo actual.');
    expect(Cobro::count())->toBe(1);
});
