<?php

// Espacio de nombres de los middleware propios.
namespace App\Http\Middleware;

// Tipo de la función "siguiente paso" de la cadena de middleware.
use Closure;
// Excepción que Laravel convierte en redirección al login (web) o 401 (API).
use Illuminate\Auth\AuthenticationException;
// Solicitud HTTP entrante.
use Illuminate\Http\Request;
// Tipo de respuesta HTTP.
use Symfony\Component\HttpFoundation\Response;

// MIDDLEWARE DE AUTORIZACIÓN POR ROL (propio de HydroPay).
// Uso en rutas: ->middleware('rol:admin') o ->middleware('rol:admin,cajero').
// Si el usuario no tiene alguno de los roles indicados recibe HTTP 403, aunque escriba la URL a mano.
// El alias "rol" se registra en bootstrap/app.php.
class VerificarRol
{
    /**
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$roles  Roles permitidos, escritos después de los dos puntos en la ruta.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Usuario autenticado de la solicitud (web por sesión o API por token).
        $usuario = $request->user();

        // Sin sesión: se lanza la excepción de autenticación (login en web, 401 en API).
        if (! $usuario) {
            throw new AuthenticationException;
        }

        // Si el rol del usuario no está en la lista permitida se detiene la solicitud con 403.
        // El intento queda registrado en el log desde bootstrap/app.php (manejo de excepciones).
        if (! in_array($usuario->rol, $roles, true)) {
            abort(403, __('hydropay.permisos.rol_insuficiente'));
        }

        // El rol es válido: la solicitud continúa hacia el controlador.
        return $next($request);
    }
}
