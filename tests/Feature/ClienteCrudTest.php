<?php

// Modelos.
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\User;

// ---------- 18.3 PRUEBAS DE VALIDACIÓN + CRUD DE CLIENTES (interfaz web) ----------

// Antes de cada prueba se crea un administrador.
beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

// Campos vacíos.
test('valida los campos obligatorios al registrar un cliente', function () {
    $this->actingAs($this->admin)->post(route('clientes.store'), [])
        ->assertSessionHasErrors(['nombre', 'numero_cuenta', 'direccion']);
});

// Datos inválidos: correo mal escrito, cuenta duplicada y teléfono con letras.
test('valida el formato del correo, el teléfono y que la cuenta sea única', function () {
    Cliente::factory()->create(['numero_cuenta' => 'HP-1']);

    $this->actingAs($this->admin)->post(route('clientes.store'), [
        'nombre' => 'Ana', 'numero_cuenta' => 'HP-1', 'direccion' => 'Calle', 'email' => 'no-es-correo', 'telefono' => 'abc',
    ])->assertSessionHasErrors(['numero_cuenta', 'email', 'telefono']);
});

// Conserva los valores capturados cuando falla la validación (old()).
test('conserva los datos capturados cuando la validación falla', function () {
    $this->actingAs($this->admin)
        ->from(route('clientes.create'))
        ->post(route('clientes.store'), ['nombre' => 'Valor conservado', 'numero_cuenta' => '', 'direccion' => ''])
        ->assertRedirect(route('clientes.create'))
        ->assertSessionHasInput('nombre', 'Valor conservado');
});

// CRUD completo con datos válidos.
test('registra, consulta, modifica y elimina un cliente', function () {
    // Crear.
    $this->actingAs($this->admin)->post(route('clientes.store'), [
        'nombre' => 'Ana', 'numero_cuenta' => 'HP-9', 'direccion' => 'Calle 5', 'telefono' => '33 1234 5678', 'activo' => 1,
    ])->assertSessionHas('mensaje');

    $cliente = Cliente::where('numero_cuenta', 'HP-9')->firstOrFail();

    // Consultar.
    $this->actingAs($this->admin)->get(route('clientes.show', $cliente))->assertOk()->assertSee('Ana');

    // Modificar.
    $this->actingAs($this->admin)->put(route('clientes.update', $cliente), [
        'nombre' => 'Ana María', 'numero_cuenta' => 'HP-9', 'direccion' => 'Calle 5',
    ])->assertSessionHas('mensaje');
    expect($cliente->fresh()->nombre)->toBe('Ana María');

    // Eliminar.
    $this->actingAs($this->admin)->delete(route('clientes.destroy', $cliente))->assertSessionHas('mensaje');
    $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
});

// Integridad: no se elimina un cliente con recibos.
test('no elimina un cliente que tiene cobros (integridad)', function () {
    $cobro = Cobro::factory()->create();

    $this->actingAs($this->admin)->delete(route('clientes.destroy', $cobro->cliente))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('clientes', ['id' => $cobro->cliente_id]);
});

// Búsqueda, filtro y paginación.
test('la búsqueda filtra por nombre y la paginación limita a 10', function () {
    Cliente::factory()->count(12)->create();
    Cliente::factory()->create(['nombre' => 'Zacarías Único']);

    $this->actingAs($this->admin)->get(route('clientes.index', ['busqueda' => 'Zacarías']))
        ->assertOk()->assertSee('Zacarías Único');

    $respuesta = $this->actingAs($this->admin)->get(route('clientes.index'));
    expect($respuesta->viewData('clientes')->count())->toBe(10);
});

// Filtro por estado del registro.
test('el filtro de estado muestra solo clientes inactivos', function () {
    Cliente::factory()->create(['nombre' => 'Cliente Activo', 'activo' => true]);
    Cliente::factory()->inactivo()->create(['nombre' => 'Cliente Inactivo']);

    $this->actingAs($this->admin)->get(route('clientes.index', ['estado' => 'inactivos']))
        ->assertOk()->assertSee('Cliente Inactivo')->assertDontSee('Cliente Activo');
});

// Sanitización: se eliminan etiquetas HTML antes de guardar.
test('el texto con etiquetas HTML se sanitiza antes de guardarse', function () {
    $this->actingAs($this->admin)->post(route('clientes.store'), [
        'nombre' => '<script>alert(1)</script>Pedro', 'numero_cuenta' => 'HP-7', 'direccion' => 'Calle',
    ]);

    expect(Cliente::where('numero_cuenta', 'HP-7')->first()->nombre)->not->toContain('<script>');
});

// Relación: el detalle muestra el adeudo calculado con los recibos del cliente.
test('el detalle del cliente muestra su adeudo actual', function () {
    $cliente = Cliente::factory()->create();
    Cobro::factory()->create(['cliente_id' => $cliente->id, 'monto' => 150]);
    Cobro::factory()->create(['cliente_id' => $cliente->id, 'monto' => 100]);

    $this->actingAs($this->admin)->get(route('clientes.show', $cliente))
        ->assertOk()
        ->assertSee('$250.00');
});
