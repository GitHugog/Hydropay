<?php

// Controladores de la API.
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CobroController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\ResumenController;
// Catálogo de zonas.
use App\Http\Controllers\Api\ZonaController;
// Fachada para declarar rutas.
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST DE HYDROPAY — versión 1
|--------------------------------------------------------------------------
| Laravel agrega el prefijo /api a todo este archivo (bootstrap/app.php).
| URL base: http://127.0.0.1:8000/api/v1
| Autenticación: token Bearer de Laravel Sanctum (POST /api/v1/auth/login).
| Habilidades del token según el rol:
|   admin    → leer, escribir, eliminar
|   cajero   → leer, escribir
|   consulta → leer
| Respuestas de error en JSON y en español (bootstrap/app.php → withExceptions).
| Documentación: docs/API.md · Colección Postman: docs/postman/
*/
Route::prefix('v1')->name('api.v1.')->group(function () {

    // Inicio de sesión: público, máximo 5 intentos por minuto por IP.
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('auth.login');

    // A partir de aquí se requiere token válido; máximo 60 solicitudes por minuto.
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

        // Revocar el token actual y consultar el perfil.
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('auth/yo', [AuthController::class, 'yo'])->name('auth.yo');

        // CONSULTAS: el token debe tener la habilidad "leer".
        Route::middleware('abilities:leer')->group(function () {
            // Indicadores del panel.
            Route::get('resumen', ResumenController::class)->name('resumen');
            // Clientes.
            Route::get('clientes', [ClienteController::class, 'index'])->name('clientes.index');
            Route::get('clientes/{cliente}', [ClienteController::class, 'show'])->name('clientes.show');
            Route::get('clientes/{cliente}/estado-cuenta', [ClienteController::class, 'estadoCuenta'])->name('clientes.estado-cuenta');
            // Catálogo de zonas (municipio y localidad).
            Route::get('zonas', ZonaController::class)->name('zonas.index');
            // Recibos.
            Route::get('cobros', [CobroController::class, 'index'])->name('cobros.index');
            Route::get('cobros/{cobro}', [CobroController::class, 'show'])->name('cobros.show');
            // Pagos.
            Route::get('pagos', [PagoController::class, 'index'])->name('pagos.index');
            Route::get('pagos/{pago}', [PagoController::class, 'show'])->name('pagos.show');
        });

        // ALTAS Y CAMBIOS: el token debe tener la habilidad "escribir".
        Route::middleware('abilities:escribir')->group(function () {
            // Clientes.
            Route::post('clientes', [ClienteController::class, 'store'])->name('clientes.store');
            Route::match(['put', 'patch'], 'clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
            // Recibos.
            Route::post('cobros', [CobroController::class, 'store'])->name('cobros.store');
            Route::match(['put', 'patch'], 'cobros/{cobro}', [CobroController::class, 'update'])->name('cobros.update');
            // Pagos (solo alta).
            Route::post('pagos', [PagoController::class, 'store'])->name('pagos.store');
        });

        // ELIMINACIONES: el token debe tener la habilidad "eliminar" (solo administrador).
        Route::middleware('abilities:eliminar')->group(function () {
            Route::delete('clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
            Route::delete('cobros/{cobro}', [CobroController::class, 'destroy'])->name('cobros.destroy');
        });
    });
});
