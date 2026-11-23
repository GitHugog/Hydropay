<?php

// Espacio de nombres de los controladores web.
namespace App\Http\Controllers;

// Modelo del usuario.
use App\Models\User;
// Evento estándar de Laravel cuando se restablece una contraseña.
use Illuminate\Auth\Events\PasswordReset;
// Respuesta de redirección.
use Illuminate\Http\RedirectResponse;
// Solicitud HTTP.
use Illuminate\Http\Request;
// Registro de eventos.
use Illuminate\Support\Facades\Log;
// Broker de contraseñas (crea y valida tokens de recuperación).
use Illuminate\Support\Facades\Password;
// Utilidades de texto.
use Illuminate\Support\Str;
// Regla de contraseña segura.
use Illuminate\Validation\Rules\Password as ReglaPassword;
// Excepción de validación.
use Illuminate\Validation\ValidationException;
// Tipo de respuesta de vista.
use Illuminate\View\View;
// Cualquier error de PHP.
use Throwable;

// RECUPERACIÓN DE CONTRASEÑA OLVIDADA mediante un enlace temporal enviado por correo (MAIL / NOTIFICATIONS).
// En desarrollo (MAIL_MAILER=log) el correo con el enlace se escribe en storage/logs/laravel.log.
class RecuperarPasswordController extends Controller
{
    // GET /olvide-mi-contrasena → formulario para escribir el correo.
    public function formularioCorreo(): View
    {
        return view('auth.forgot-password');
    }

    // POST /olvide-mi-contrasena → genera el token y envía el enlace.
    public function enviarEnlace(Request $request): RedirectResponse
    {
        // El correo es obligatorio y con formato válido.
        $request->validate(['email' => ['required', 'email']]);

        try {
            // Laravel crea el token (tabla password_reset_tokens) y envía la notificación ResetPassword.
            Password::sendResetLink($request->only('email'));
        } catch (Throwable $e) {
            // Si el correo falla (SMTP caído, etc.) se registra el error sin romper la pantalla.
            Log::error('No se pudo enviar el correo de recuperación', ['error' => $e->getMessage()]);
        }

        // Bitácora de la solicitud.
        Log::info('Solicitud de recuperación de contraseña', ['email' => $request->input('email')]);

        // Mensaje genérico: no revela si el correo existe (evita enumerar usuarios).
        return back()->with('status', __('hydropay.auth.enlace_enviado'));
    }

    // GET /restablecer-contrasena/{token} → formulario de nueva contraseña (llega desde el correo).
    public function formularioNuevaPassword(Request $request, string $token): View
    {
        // El token viaja en la URL y el correo como parámetro ?email=
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    // POST /restablecer-contrasena → valida el token y guarda la nueva contraseña.
    public function restablecer(Request $request): RedirectResponse
    {
        // Reglas: token, correo y contraseña segura confirmada.
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', ReglaPassword::min(8)->letters()->numbers()],
        ]);

        // El broker comprueba que el token exista, no haya expirado (60 min) y corresponda al correo.
        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $usuario, string $password) {
                // El cast "hashed" cifra la contraseña; se cambia remember_token para cerrar sesiones "recordadas".
                $usuario->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                // Por seguridad se revocan también los tokens de la API.
                $usuario->tokens()->delete();

                // Evento estándar de Laravel.
                event(new PasswordReset($usuario));

                // Bitácora.
                Log::info('Contraseña restablecida', ['user_id' => $usuario->id]);
            }
        );

        // Si el token es inválido o venció se muestra el error traducido (lang/es/passwords.php).
        if ($estado !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($estado)]);
        }

        // Éxito: regresa al login.
        return redirect()->route('login')->with('status', __('hydropay.auth.password_restablecida'));
    }
}
