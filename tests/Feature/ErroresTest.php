<?php

// Modelos.
use App\Models\Cliente;
use App\Models\User;
// Fachada de log (se espía para comprobar el registro de eventos).
use Illuminate\Support\Facades\Log;

// ---------- PRUEBAS DE MANEJO DE ERRORES Y LOGGING (interfaz web) ----------

// Página 404 propia en español.
test('una dirección inexistente muestra la página 404 en español', function () {
    $this->get('/esta-ruta-no-existe')
        ->assertNotFound()
        ->assertSee('Página no encontrada');
});

// Un registro inexistente también da 404.
test('un cliente inexistente responde 404', function () {
    $this->actingAs(User::factory()->create())
        ->get('/clientes/999999')
        ->assertNotFound();
});

// Página 403 propia con el motivo del rechazo.
test('el acceso denegado muestra la página 403 con el motivo', function () {
    $this->actingAs(User::factory()->cajero()->create())
        ->get(route('usuarios.index'))
        ->assertForbidden()
        ->assertSee('Acceso denegado');
});

// LOGGING: cada 403 real queda registrado.
test('un acceso denegado real se registra en el log', function () {
    Log::spy();

    $this->actingAs(User::factory()->cajero()->create())->get(route('usuarios.index'))->assertForbidden();

    Log::shouldHaveReceived('warning')->withArgs(fn ($mensaje) => $mensaje === 'Acceso denegado (403)');
});

// Corrección: las comprobaciones @can de las vistas ya no llenan el log de avisos falsos.
test('ver un listado con botones ocultos por @can no genera avisos en el log', function () {
    Cliente::factory()->count(3)->create();
    Log::spy();

    $this->actingAs(User::factory()->cajero()->create())->get(route('clientes.index'))->assertOk();

    Log::shouldNotHaveReceived('warning');
});
