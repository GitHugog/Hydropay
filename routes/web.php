<?php

// Controladores que atienden cada módulo de la interfaz web.
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CobroController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\RecuperarPasswordController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
// Controlador del catálogo de zonas.
use App\Http\Controllers\ZonaController;
// Fachada para declarar rutas.
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RUTAS WEB DE HYDROPAY
|--------------------------------------------------------------------------
| Todas las rutas tienen nombre (->name) para usarlas con route('nombre').
| Capas de seguridad:
|   1. "guest"  → solo visitantes sin sesión (login y recuperación).
|   2. "auth"   → requiere sesión iniciada.
|   3. "rol:.." → middleware propio VerificarRol: limita por rol aunque se escriba la URL a mano.
|   4. Policies → reglas finas por registro dentro de cada controlador o Form Request.
*/

// La dirección raíz redirige al panel (si no hay sesión, "auth" envía al login).
Route::redirect('/', '/dashboard')->name('inicio');

/*
|--------------------------------------------------------------------------
| Visitantes (sin sesión)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Formulario de inicio de sesión.
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    // Envío de credenciales.
    Route::post('/login', [AuthController::class, 'login'])->name('login.intento');

    // Solicitud del enlace de recuperación (máximo 5 por minuto para evitar abuso).
    Route::get('/olvide-mi-contrasena', [RecuperarPasswordController::class, 'formularioCorreo'])->name('password.request');
    Route::post('/olvide-mi-contrasena', [RecuperarPasswordController::class, 'enviarEnlace'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    // Formulario de nueva contraseña: {token} es un parámetro OBLIGATORIO de la ruta.
    Route::get('/restablecer-contrasena/{token}', [RecuperarPasswordController::class, 'formularioNuevaPassword'])->name('password.reset');
    // Guardado de la nueva contraseña (máximo 5 intentos por minuto).
    Route::post('/restablecer-contrasena', [RecuperarPasswordController::class, 'restablecer'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Usuarios autenticados (cualquier rol)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Cierre de sesión por POST (lleva token CSRF).
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Panel principal.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Cambio de contraseña del usuario actual.
    Route::get('/cuenta/contrasena', [CuentaController::class, 'editarPassword'])->name('cuenta.password.edit');
    Route::put('/cuenta/contrasena', [CuentaController::class, 'actualizarPassword'])->name('cuenta.password.update');

    /*
    |----------------------------------------------------------------------
    | Registro y edición: solo administrador y cajero (rol:admin,cajero)
    | Se declaran ANTES de las rutas de consulta para que /clientes/create
    | no se confunda con /clientes/{cliente}.
    |----------------------------------------------------------------------
    */
    Route::middleware('rol:admin,cajero')->group(function () {
        // Alta y edición de clientes.
        Route::resource('clientes', ClienteController::class)->only(['create', 'store', 'edit', 'update']);
        // Alta y edición de recibos.
        Route::resource('cobros', CobroController::class)->only(['create', 'store', 'edit', 'update']);
        // Registro de pagos (los pagos no se editan).
        Route::resource('pagos', PagoController::class)->only(['create', 'store']);
    });

    /*
    |----------------------------------------------------------------------
    | Consulta: todos los roles
    |----------------------------------------------------------------------
    */
    Route::resource('clientes', ClienteController::class)->only(['index', 'show']);
    Route::resource('cobros', CobroController::class)->only(['index', 'show']);
    Route::resource('pagos', PagoController::class)->only(['index', 'show']);

    // Descarga protegida del comprobante de un pago.
    Route::get('/pagos/{pago}/comprobante', [PagoController::class, 'comprobante'])->name('pagos.comprobante');

    // Reportes mensuales con parámetros OPCIONALES: /reportes, /reportes/2026, /reportes/2026/11.
    Route::get('/reportes/{anio?}/{mes?}', [ReporteController::class, 'index'])
        ->whereNumber(['anio', 'mes'])     // Solo dígitos; cualquier otro valor da 404.
        ->name('reportes.index');

    // Exportación del reporte a CSV: parámetros OBLIGATORIOS.
    Route::get('/reportes/{anio}/{mes}/csv', [ReporteController::class, 'exportar'])
        ->whereNumber(['anio', 'mes'])
        ->name('reportes.csv');

    /*
    |----------------------------------------------------------------------
    | Solo administrador (rol:admin)
    | Cualquier otro rol recibe 403 aunque escriba la URL a mano.
    |----------------------------------------------------------------------
    */
    Route::middleware('rol:admin')->group(function () {
        // Eliminación de clientes y recibos (los pagos nunca se eliminan).
        Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
        Route::delete('/cobros/{cobro}', [CobroController::class, 'destroy'])->name('cobros.destroy');

        // Genera el recibo del periodo actual a todos los clientes con cuota (mensual, bimestral o anual).
        Route::post('/cobros/generar', [CobroController::class, 'generar'])->name('cobros.generar');

        // Gestión de usuarios y roles (no necesita vista de detalle).
        Route::resource('usuarios', UsuarioController::class)->except('show');

        // Catálogo de zonas: municipio y localidad de los clientes (no necesita vista de detalle).
        Route::resource('zonas', ZonaController::class)->except('show');
    });
});
