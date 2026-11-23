<?php

// Espacio de nombres de los controladores de la API REST.
namespace App\Http\Controllers\Api;

// Controlador base.
use App\Http\Controllers\Controller;
// Resource del usuario.
use App\Http\Resources\UsuarioResource;
// Modelo del usuario.
use App\Models\User;
// Respuesta JSON.
use Illuminate\Http\JsonResponse;
// Solicitud HTTP.
use Illuminate\Http\Request;
// Comparación segura de contraseñas contra el hash.
use Illuminate\Support\Facades\Hash;
// Registro de eventos.
use Illuminate\Support\Facades\Log;

// AUTENTICACIÓN DE LA API con Laravel Sanctum (tokens personales tipo Bearer).
//   POST /api/v1/auth/login   → entrega un token.
//   POST /api/v1/auth/logout  → revoca el token usado.
//   GET  /api/v1/auth/yo      → datos del usuario dueño del token.
class AuthController extends Controller
{
    // POST /api/v1/auth/login (público, máximo 5 intentos por minuto).
    public function login(Request $request): JsonResponse
    {
        // Validación de entrada (si falla, Laravel responde 422 con los errores en JSON).
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'dispositivo' => ['nullable', 'string', 'max:60'], // Nombre del cliente: "Postman", "App móvil"...
        ]);

        // Busca al usuario por su correo (en minúsculas).
        $usuario = User::where('email', mb_strtolower($datos['email']))->first();

        // Si no existe o la contraseña no coincide con el hash se responde 401 con un mensaje genérico.
        if (! $usuario || ! Hash::check($datos['password'], $usuario->password)) {
            // Bitácora del intento fallido.
            Log::warning('API: intento de inicio de sesión fallido', ['email' => $datos['email'], 'ip' => $request->ip()]);

            // 401 = no autenticado.
            return response()->json(['message' => __('hydropay.api.credenciales_invalidas')], 401);
        }

        // Fecha y hora en que vencerá el token (config/hydropay.php → api_token_horas).
        $expira = now()->addHours(config('hydropay.api_token_horas'));

        // Crea el token con las habilidades del rol (leer / escribir / eliminar) y su vencimiento.
        $token = $usuario->createToken($datos['dispositivo'] ?? 'api', $usuario->habilidadesApi(), $expira);

        // Bitácora del acceso por API.
        Log::info('API: token emitido', ['user_id' => $usuario->id, 'rol' => $usuario->rol, 'expira' => $expira->toIso8601String()]);

        // 201 = recurso creado (el token). plainTextToken solo se muestra esta única vez.
        return response()->json([
            'message' => __('hydropay.api.token_emitido'),
            'token' => $token->plainTextToken,
            'tipo' => 'Bearer',
            'expira_en' => $expira->toIso8601String(),
            'habilidades' => $usuario->habilidadesApi(),
            'usuario' => new UsuarioResource($usuario),
        ], 201);
    }

    // POST /api/v1/auth/logout → elimina de la base el token con el que se hizo la solicitud.
    public function logout(Request $request): JsonResponse
    {
        // Token actual del usuario autenticado.
        $request->user()->currentAccessToken()->delete();

        // Bitácora.
        Log::info('API: token revocado', ['user_id' => $request->user()->id]);

        // Confirmación.
        return response()->json(['message' => __('hydropay.api.token_revocado')]);
    }

    // GET /api/v1/auth/yo → perfil del usuario y datos del token.
    public function yo(Request $request): JsonResponse
    {
        // Token usado en la solicitud.
        $token = $request->user()->currentAccessToken();

        // Respuesta con el usuario, sus habilidades y la fecha de vencimiento.
        return response()->json([
            'usuario' => new UsuarioResource($request->user()),
            'habilidades' => $token->abilities ?? [],
            'expira_en' => $token->expires_at?->toIso8601String(),
        ]);
    }
}
