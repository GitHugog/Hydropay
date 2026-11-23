<?php

// Espacio de nombres de los controladores web.
namespace App\Http\Controllers;

// Respuesta de redirección.
use Illuminate\Http\RedirectResponse;
// Solicitud HTTP.
use Illuminate\Http\Request;
// Registro de eventos.
use Illuminate\Support\Facades\Log;
// Regla de contraseña segura.
use Illuminate\Validation\Rules\Password;
// Tipo de respuesta de vista.
use Illuminate\View\View;

// CAMBIO DE CONTRASEÑA del usuario que ya inició sesión.
class CuentaController extends Controller
{
    // GET /cuenta/contrasena → formulario.
    public function editarPassword(): View
    {
        return view('cuenta.password');
    }

    // PUT /cuenta/contrasena → valida y guarda la nueva contraseña.
    public function actualizarPassword(Request $request): RedirectResponse
    {
        // Validación de los tres campos del formulario.
        $datos = $request->validate([
            // "current_password" compara lo escrito contra el hash guardado del usuario actual.
            'password_actual' => ['required', 'current_password'],
            // Nueva contraseña: confirmada, distinta de la actual, mínimo 8 con letras y números.
            'password' => ['required', 'confirmed', 'different:password_actual', Password::min(8)->letters()->numbers()],
        ]);

        // El cast "hashed" del modelo cifra la nueva contraseña.
        $request->user()->update(['password' => $datos['password']]);

        // Bitácora.
        Log::info('Contraseña cambiada por el usuario', ['user_id' => $request->user()->id]);

        // Regresa al panel con mensaje de éxito.
        return redirect()->route('dashboard')->with('mensaje', __('hydropay.auth.password_cambiada'));
    }
}
