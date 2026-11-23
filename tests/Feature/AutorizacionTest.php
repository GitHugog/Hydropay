<?php

// Modelos.
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Pago;
use App\Models\User;
// Comparación de contraseñas.
use Illuminate\Support\Facades\Hash;

// ---------- 18.4 PRUEBAS DE AUTORIZACIÓN (roles, middleware VerificarRol y Policies) ----------

// Usuario autorizado: el administrador entra a Usuarios.
test('el administrador entra al módulo de usuarios', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('usuarios.index'))->assertOk();
});

// Usuario no autorizado: el cajero escribe la URL a mano y recibe 403 (middleware rol:admin).
test('el cajero recibe 403 al abrir usuarios por URL (middleware rol:admin)', function () {
    $this->actingAs(User::factory()->cajero()->create())
        ->get('/usuarios')->assertForbidden();
});

// Usuario no autorizado: consulta tampoco entra.
test('el usuario de consulta recibe 403 al abrir usuarios por URL', function () {
    $this->actingAs(User::factory()->create())
        ->get('/usuarios')->assertForbidden();
});

// Consulta: puede ver pero no crear (middleware rol:admin,cajero).
test('el usuario de consulta puede ver clientes pero no crearlos', function () {
    $consulta = User::factory()->create();

    $this->actingAs($consulta)->get(route('clientes.index'))->assertOk();
    $this->actingAs($consulta)->get(route('clientes.create'))->assertForbidden();
    $this->actingAs($consulta)->post(route('clientes.store'), [
        'nombre' => 'X', 'numero_cuenta' => 'HP-1', 'direccion' => 'Calle 1',
    ])->assertForbidden();

    expect(Cliente::count())->toBe(0);
});

// Consulta: tampoco registra pagos ni recibos.
test('el usuario de consulta no puede registrar recibos ni pagos', function () {
    $consulta = User::factory()->create();
    $cobro = Cobro::factory()->create();

    $this->actingAs($consulta)->get(route('cobros.create'))->assertForbidden();
    $this->actingAs($consulta)->get(route('pagos.create'))->assertForbidden();
    $this->actingAs($consulta)->post(route('pagos.store'), [
        'cobro_id' => $cobro->id, 'metodo' => 'efectivo', 'fecha_pago' => now()->toDateString(),
    ])->assertForbidden();

    expect(Pago::count())->toBe(0);
});

// Cajero autorizado: registra y edita clientes.
test('el cajero registra y edita clientes', function () {
    $cajero = User::factory()->cajero()->create();

    $this->actingAs($cajero)->post(route('clientes.store'), [
        'nombre' => 'Luis', 'numero_cuenta' => 'HP-100', 'direccion' => 'Calle 1', 'activo' => 1,
    ])->assertRedirect(route('clientes.show', Cliente::firstOrFail()));

    $cliente = Cliente::firstOrFail();

    $this->actingAs($cajero)->put(route('clientes.update', $cliente), [
        'nombre' => 'Luis Editado', 'numero_cuenta' => 'HP-100', 'direccion' => 'Calle 2',
    ])->assertRedirect(route('clientes.show', $cliente));

    expect($cliente->fresh()->nombre)->toBe('Luis Editado');
});

// Eliminación: solo administrador.
test('el cajero no puede eliminar clientes (403) pero el administrador sí', function () {
    $cliente = Cliente::factory()->create();

    $this->actingAs(User::factory()->cajero()->create())
        ->delete(route('clientes.destroy', $cliente))->assertForbidden();
    expect(Cliente::count())->toBe(1);

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('clientes.destroy', $cliente))->assertRedirect(route('clientes.index'));
    expect(Cliente::count())->toBe(0);
});

// Policy: un recibo pagado no se puede editar aunque se escriba la URL.
test('un recibo pagado no se puede editar escribiendo la URL (CobroPolicy)', function () {
    $pago = Pago::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('cobros.edit', $pago->cobro))
        ->assertForbidden()
        ->assertSee('ya fue liquidado');
});

// Protección: el administrador no puede quitarse su rol.
test('el administrador no puede quitarse su propio rol', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->put(route('usuarios.update', $admin), [
        'name' => $admin->name, 'email' => $admin->email, 'rol' => 'consulta',
    ]);

    expect($admin->fresh()->rol)->toBe('admin');
});

// Protección: nadie elimina su propia cuenta.
test('un administrador no puede eliminar su propia cuenta', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->delete(route('usuarios.destroy', $admin))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('users', ['id' => $admin->id, 'rol' => User::ROL_ADMIN]);
});

// Registro de usuarios por el administrador + hashing.
test('el administrador registra usuarios y las contraseñas se guardan con hash', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('usuarios.store'), [
        'name' => 'Cajera de prueba',
        'email' => 'Cajera.Nueva@Example.com',
        'rol' => User::ROL_CAJERO,
        'password' => 'ClaveSegura123',
        'password_confirmation' => 'ClaveSegura123',
    ])->assertRedirect(route('usuarios.index'));

    // El correo se normaliza a minúsculas.
    $usuario = User::where('email', 'cajera.nueva@example.com')->firstOrFail();

    expect($usuario->rol)->toBe(User::ROL_CAJERO);
    expect($usuario->password)->not->toBe('ClaveSegura123');
    expect(Hash::check('ClaveSegura123', $usuario->password))->toBeTrue();
});

// Validación del alta de usuarios: rol inexistente y contraseña débil.
test('el alta de usuarios rechaza un rol inexistente y una contraseña débil', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('usuarios.store'), [
        'name' => 'Intruso',
        'email' => 'intruso@example.com',
        'rol' => 'superadmin',
        'password' => '12345678',
        'password_confirmation' => '12345678',
    ])->assertSessionHasErrors(['rol', 'password']);
});
