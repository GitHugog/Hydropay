<?php

// Middleware propio de autorización por rol.
use App\Http\Middleware\VerificarRol;
// Excepción de "no autenticado".
use Illuminate\Auth\AuthenticationException;
// Clase principal de la aplicación.
use Illuminate\Foundation\Application;
// Configurador del manejo de excepciones.
use Illuminate\Foundation\Configuration\Exceptions;
// Configurador de middleware.
use Illuminate\Foundation\Configuration\Middleware;
// Solicitud HTTP.
use Illuminate\Http\Request;
// Registro de eventos.
use Illuminate\Support\Facades\Log;
// Middleware de Sanctum que revisa las habilidades del token.
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
// Interfaz común de las excepciones HTTP (403, 404, 405, 429...).
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

// Punto de arranque de HydroPay: rutas, middleware y manejo de errores.
return Application::configure(basePath: dirname(__DIR__))
    // Archivos de rutas.
    ->withRouting(
        web: __DIR__.'/../routes/web.php',          // Interfaz web (sesión + CSRF).
        api: __DIR__.'/../routes/api.php',          // API REST (prefijo /api, sin sesión, token Sanctum).
        commands: __DIR__.'/../routes/console.php', // Tareas programadas.
        health: '/up',                              // Ruta de salud para monitoreo.
    )
    // Configuración de middleware.
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias para usar en las rutas.
        $middleware->alias([
            'rol' => VerificarRol::class,           // rol:admin  |  rol:admin,cajero
            'abilities' => CheckAbilities::class,   // El token debe tener TODAS las habilidades indicadas.
            'ability' => CheckForAnyAbility::class, // El token debe tener AL MENOS UNA de las habilidades.
        ]);

        // Quien no ha iniciado sesión va al login; quien ya la tiene y abre el login va al panel.
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    // Manejo centralizado de errores.
    ->withExceptions(function (Exceptions $exceptions): void {
        // Las solicitudes a /api/* (o que piden JSON) reciben los errores en formato JSON.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // API sin token o con token vencido → 401 con mensaje en español.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            // En la web se deja el comportamiento normal (redirigir al login).
            if ($request->is('api/*')) {
                return response()->json(['message' => __('hydropay.api.no_autenticado')], 401);
            }

            // null = Laravel continúa con su respuesta predeterminada.
            return null;
        });

        // Errores HTTP (403, 404, 405, 419, 429, 500...).
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            // Código de estado de la excepción.
            $estado = $e->getStatusCode();

            // LOGGING: cada acceso realmente denegado (middleware, Policy o Form Request) queda registrado.
            if ($estado === 403) {
                Log::warning('Acceso denegado (403)', [
                    'user_id' => $request->user()?->id,
                    'rol' => $request->user()?->rol,
                    'metodo' => $request->method(),
                    'url' => $request->fullUrl(),
                    'motivo' => $e->getMessage(),
                ]);
            }

            // En la web se usan las vistas resources/views/errors/{código}.blade.php.
            if (! $request->is('api/*')) {
                return null;
            }

            // Mensaje original de la excepción (las Policies y abort() de HydroPay ya vienen en español).
            $original = $e->getMessage();

            // Mensajes predeterminados de Laravel/Symfony (en inglés) que se reemplazan por uno en español.
            $mensajesEnIngles = ['This action is unauthorized', 'No query results', 'The route', 'Too Many Attempts', 'Not found', 'Bad request', 'Unauthenticated'];

            // ¿El mensaje original es uno de los predeterminados en inglés?
            $esPredeterminado = $original === ''
                || str_contains($original, 'method is not supported')
                || collect($mensajesEnIngles)->contains(fn (string $inicio) => str_starts_with($original, $inicio));

            // Clave de traducción del mensaje genérico para este código (lang/es/hydropay.php).
            $clave = 'hydropay.api.errores.'.$estado;

            // Se elige el mensaje final.
            $mensaje = match (true) {
                // Token sin la habilidad requerida por la ruta (middleware abilities de Sanctum).
                str_starts_with($original, 'Invalid ability') => __('hydropay.api.sin_habilidad'),
                // Mensaje en inglés con traducción disponible para su código.
                $esPredeterminado && trans()->has($clave) => __($clave),
                // Mensaje en inglés sin traducción específica.
                $esPredeterminado => __('hydropay.api.errores.general'),
                // Mensaje propio de HydroPay: se conserva.
                default => $original,
            };

            // Respuesta JSON conservando encabezados como Retry-After (429).
            return response()->json(['message' => $mensaje], $estado, $e->getHeaders());
        });
    })->create();
