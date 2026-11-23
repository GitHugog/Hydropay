<?php

// Espacio de nombres de los controladores web.
namespace App\Http\Controllers;

// Respuesta de redirección.
use Illuminate\Http\RedirectResponse;
// Solicitud HTTP.
use Illuminate\Http\Request;
// Fachada de autenticación.
use Illuminate\Support\Facades\Auth;
// Registro de eventos.
use Illuminate\Support\Facades\Log;
// Limitador de intentos (protección contra fuerza bruta).
use Illuminate\Support\Facades\RateLimiter;
// Utilidades de texto.
use Illuminate\Support\Str;
// Excepción de validación (muestra el error en el formulario).
use Illuminate\Validation\ValidationException;
// Tipo de respuesta de vista.
use Illuminate\View\View;

// CONTROLADOR DE INICIO Y CIERRE DE SESIÓN (interfaz web).
// Las cuentas del personal las crea el administrador en UsuarioController; no hay auto-registro público.
class AuthController extends Controller
{
    // GET /login → formulario de inicio de sesión.
    public function showLogin(): View
    {
        return view('auth.login');
    }

    // POST /login → valida credenciales e inicia la sesión.
    public function login(Request $request): RedirectResponse
    {
        // Validación básica: correo con formato válido y contraseña presente.
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Llave del limitador: correo en minúsculas + IP (5 intentos por minuto).
        $clave = Str::lower($credenciales['email']).'|'.$request->ip();

        // Si ya se agotaron los intentos se bloquea temporalmente.
        if (RateLimiter::tooManyAttempts($clave, 5)) {
            // Segundos que faltan para volver a intentar.
            $segundos = RateLimiter::availableIn($clave);

            // Se registra el bloqueo.
            Log::warning('Inicio de sesión bloqueado por demasiados intentos', [
                'email' => $credenciales['email'],
                'ip' => $request->ip(),
            ]);

            // Mensaje traducido (lang/es/auth.php).
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => $segundos]),
            ]);
        }

        // Auth::attempt busca el correo y compara la contraseña contra el hash guardado.
        if (! Auth::attempt($credenciales, $request->boolean('recordar'))) {
            // Cuenta el intento fallido (expira en 60 segundos).
            RateLimiter::hit($clave, 60);

            // Bitácora del intento fallido.
            Log::warning('Intento de inicio de sesión fallido', [
                'email' => $credenciales['email'],
                'ip' => $request->ip(),
            ]);

            // Mensaje genérico: no revela si el correo existe.
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Credenciales correctas: se reinicia el contador de intentos.
        RateLimiter::clear($clave);

        // Nuevo ID de sesión para evitar la fijación de sesión.
        $request->session()->regenerate();

        // Bitácora del acceso.
        Log::info('Inicio de sesión correcto', [
            'user_id' => Auth::id(),
            'rol' => Auth::user()->rol,
        ]);

        // Redirige a la página que quería visitar o al panel principal.
        return redirect()->intended(route('dashboard'));
    }

    // POST /logout → cierra la sesión (POST para que lleve token CSRF).
    public function logout(Request $request): RedirectResponse
    {
        // Bitácora antes de perder el ID del usuario.
        Log::info('Cierre de sesión', ['user_id' => Auth::id()]);

        // Cierra la sesión del guard web.
        Auth::logout();

        // Invalida la sesión y genera un nuevo token CSRF.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Regresa al login con mensaje informativo.
        return redirect()->route('login')->with('status', __('hydropay.auth.sesion_cerrada'));
    }
}
