<?php

// Modelos.
use App\Models\Cliente;
use App\Models\User;
use App\Models\Zona;
// Autenticación por token en las pruebas de la API.
use Laravel\Sanctum\Sanctum;

// ---------- ZONAS: MUNICIPIO Y LOCALIDAD DE LOS CLIENTES ----------

// Usuarios de cada rol para todas las pruebas del archivo.
beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->cajero = User::factory()->cajero()->create();
});

// CRUD completo del catálogo por el administrador.
test('el administrador registra, edita y elimina una zona', function () {
    $this->actingAs($this->admin)->post(route('zonas.store'), ['municipio' => 'Tala', 'localidad' => 'Cuisillos'])
        ->assertRedirect(route('zonas.index'));

    $zona = Zona::firstOrFail();
    $this->actingAs($this->admin)->get(route('zonas.index'))->assertOk()->assertSee('Cuisillos');

    $this->actingAs($this->admin)->put(route('zonas.update', $zona), ['municipio' => 'Tala', 'localidad' => 'El Refugio'])
        ->assertRedirect(route('zonas.index'));
    expect($zona->fresh()->localidad)->toBe('El Refugio');

    $this->actingAs($this->admin)->delete(route('zonas.destroy', $zona))->assertRedirect(route('zonas.index'));
    expect(Zona::count())->toBe(0);
});

// Validación: campos obligatorios y localidad única dentro del municipio.
test('valida campos vacíos y no repite una localidad en el mismo municipio', function () {
    $this->actingAs($this->admin)->post(route('zonas.store'), ['municipio' => '', 'localidad' => ''])
        ->assertSessionHasErrors(['municipio', 'localidad']);

    Zona::create(['municipio' => 'Tala', 'localidad' => 'Cuisillos']);

    $this->actingAs($this->admin)->post(route('zonas.store'), ['municipio' => 'Tala', 'localidad' => 'Cuisillos'])
        ->assertSessionHasErrors(['localidad' => 'Esa localidad ya está registrada en ese municipio.']);

    // La misma localidad en otro municipio sí se permite.
    $this->actingAs($this->admin)->post(route('zonas.store'), ['municipio' => 'Ameca', 'localidad' => 'Cuisillos'])
        ->assertSessionHasNoErrors();
    expect(Zona::count())->toBe(2);
});

// Autorización: el catálogo es solo del administrador.
test('el cajero recibe 403 en el catálogo de zonas', function () {
    $this->actingAs($this->cajero)->get(route('zonas.index'))->assertForbidden();
    $this->actingAs($this->cajero)->post(route('zonas.store'), ['municipio' => 'Tala', 'localidad' => 'X'])->assertForbidden();
});

// Integridad: no se elimina una zona con clientes.
test('no elimina una zona que tiene clientes asignados', function () {
    $zona = Zona::factory()->create();
    Cliente::factory()->create(['zona_id' => $zona->id]);

    $this->actingAs($this->admin)->delete(route('zonas.destroy', $zona))
        ->assertSessionHas('error');
    expect(Zona::count())->toBe(1);
});

// El cliente se registra con zona y el listado se filtra por zona.
test('asigna zona al cliente y filtra el listado por zona', function () {
    $cuisillos = Zona::create(['municipio' => 'Tala', 'localidad' => 'Cuisillos']);
    $refugio = Zona::create(['municipio' => 'Tala', 'localidad' => 'El Refugio']);

    $this->actingAs($this->cajero)->post(route('clientes.store'), [
        'nombre' => 'Marta de Cuisillos', 'numero_cuenta' => 'HP-700', 'direccion' => 'Calle 5', 'activo' => 1,
        'zona_id' => $cuisillos->id,
    ])->assertSessionHasNoErrors();
    Cliente::factory()->create(['nombre' => 'Pablo de El Refugio', 'zona_id' => $refugio->id]);

    expect(Cliente::where('numero_cuenta', 'HP-700')->first()->zona->localidad)->toBe('Cuisillos');

    $this->actingAs($this->cajero)->get(route('clientes.index', ['zona' => $cuisillos->id]))
        ->assertOk()->assertSee('Marta de Cuisillos')->assertDontSee('Pablo de El Refugio');

    // Una zona inexistente se rechaza.
    $this->actingAs($this->cajero)->post(route('clientes.store'), [
        'nombre' => 'X', 'numero_cuenta' => 'HP-701', 'direccion' => 'Y', 'zona_id' => 9999,
    ])->assertSessionHasErrors('zona_id');
});

// API: catálogo de zonas y zona dentro del cliente.
test('la API lista las zonas y devuelve la zona de cada cliente', function () {
    $zona = Zona::create(['municipio' => 'Tala', 'localidad' => 'Cuisillos']);
    $cliente = Cliente::factory()->create(['zona_id' => $zona->id]);
    Sanctum::actingAs($this->cajero, ['leer']);

    $this->getJson('/api/v1/zonas')->assertOk()
        ->assertJsonPath('data.0.localidad', 'Cuisillos')
        ->assertJsonPath('data.0.clientes', 1);

    $this->getJson("/api/v1/clientes/{$cliente->id}")->assertOk()
        ->assertJsonPath('data.zona.localidad', 'Cuisillos')
        ->assertJsonPath('data.zona.municipio', 'Tala');

    $this->getJson("/api/v1/clientes?zona_id={$zona->id}")->assertOk()->assertJsonCount(1, 'data');
});
