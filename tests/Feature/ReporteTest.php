<?php

// Modelos.
use App\Models\Cobro;
use App\Models\Pago;
use App\Models\User;

// ---------- PRUEBAS DEL MÓDULO DE REPORTES MENSUALES ----------

// Usuario de consulta (los reportes son de solo lectura).
beforeEach(function () {
    $this->consulta = User::factory()->create();
});

// Sin sesión no hay reportes.
test('un visitante sin sesión no puede ver reportes', function () {
    $this->get(route('reportes.index'))->assertRedirect(route('login'));
});

// Ruta sin parámetros: mes actual.
test('sin parámetros muestra el reporte del mes actual', function () {
    $this->actingAs($this->consulta)->get(route('reportes.index'))
        ->assertOk()
        ->assertSee('Reporte mensual');
});

// Parámetros opcionales: suma solo los pagos del mes indicado (incluido el último día).
test('con año y mes calcula los ingresos solo de ese mes', function () {
    // Dos pagos en noviembre (uno el día 30) y uno en octubre.
    Pago::factory()->create(['fecha_pago' => '2026-11-03']);
    Pago::factory()->create(['fecha_pago' => '2026-11-30']);
    Pago::factory()->create(['fecha_pago' => '2026-10-15']);

    $respuesta = $this->actingAs($this->consulta)->get(route('reportes.index', ['anio' => 2026, 'mes' => 11]))->assertOk();

    // Solo cuentan los dos pagos de noviembre.
    $reporte = $respuesta->viewData('reporte');
    expect($reporte['ingresos']['cantidad'])->toBe(2);
    expect($reporte['ingresos']['total'])->toBe(round((float) Pago::whereDate('fecha_pago', '>=', '2026-11-01')->sum('monto'), 2));
});

// Un mes inexistente responde 404.
test('un mes fuera de rango responde 404', function () {
    $this->actingAs($this->consulta)->get('/reportes/2026/13')->assertNotFound();
});

// Un parámetro no numérico no coincide con la ruta (whereNumber).
test('un parámetro no numérico responde 404', function () {
    $this->actingAs($this->consulta)->get('/reportes/abc')->assertNotFound();
});

// El selector de mes redirige a la URL con parámetros.
test('el selector de mes redirige a la ruta con año y mes', function () {
    $this->actingAs($this->consulta)->get(route('reportes.index', ['periodo' => '2026-10']))
        ->assertRedirect(route('reportes.index', ['anio' => 2026, 'mes' => 10]));
});

// Los principales deudores salen de los recibos vencidos.
test('el reporte lista a los clientes con adeudo vencido', function () {
    $cobro = Cobro::factory()->vencido()->create(['monto' => 500]);

    $respuesta = $this->actingAs($this->consulta)->get(route('reportes.index'))->assertOk();

    expect($respuesta->viewData('reporte')['deudores']->first()['cliente_id'])->toBe($cobro->cliente_id);
});

// Exportación CSV.
test('exporta el detalle de pagos del mes en CSV', function () {
    $pago = Pago::factory()->create(['fecha_pago' => '2026-11-10']);

    $respuesta = $this->actingAs($this->consulta)->get(route('reportes.csv', ['anio' => 2026, 'mes' => 11]));

    $respuesta->assertOk();
    expect($respuesta->headers->get('content-type'))->toContain('text/csv');
    expect($respuesta->streamedContent())
        ->toContain('Fecha de pago')
        ->toContain($pago->cobro->cliente->numero_cuenta);
});
