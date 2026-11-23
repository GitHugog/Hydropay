<?php

// Espacio de nombres de los proveedores de servicios.
namespace App\Providers;

// Modelo del usuario.
use App\Models\User;
// Configuración de la paginación.
use Illuminate\Pagination\Paginator;
// Fachada de autorización.
use Illuminate\Support\Facades\Gate;
// Clase base de los proveedores de servicios.
use Illuminate\Support\ServiceProvider;

// Proveedor principal: configuración que se ejecuta al arrancar HydroPay.
class AppServiceProvider extends ServiceProvider
{
    // Registro de servicios en el contenedor (los servicios de HydroPay se resuelven solos por su tipo).
    public function register(): void
    {
        //
    }

    // Configuración al arrancar la aplicación.
    public function boot(): void
    {
        // Usa la vista de paginación propia (resources/views/vendor/pagination/hydropay.blade.php).
        Paginator::defaultView('vendor.pagination.hydropay');

        // GATE sin modelo: solo el administrador gestiona usuarios. Se usa en el menú (@can) y en UsuarioRequest.
        Gate::define('gestionar-usuarios', fn (User $usuario) => $usuario->esAdmin());

        // GATE sin modelo: solo el administrador administra catálogos (zonas). Lo usan ZonaRequest y el menú.
        Gate::define('administrar-catalogos', fn (User $usuario) => $usuario->esAdmin());

        // Nota: los accesos denegados ya NO se registran aquí con Gate::after, porque también se disparaba
        // con cada @can de las vistas (avisos falsos en el log). Ahora se registran solo los 403 reales
        // en bootstrap/app.php → withExceptions.
    }
}
