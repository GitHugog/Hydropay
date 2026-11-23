<?php

// Espacio de nombres de los Form Requests.
namespace App\Http\Requests;

// Rasgo que limpia etiquetas HTML antes de validar.
use App\Http\Requests\Concerns\LimpiaEntradas;
// Modelo del usuario.
use App\Models\User;
// Clase base de las solicitudes con validación.
use Illuminate\Foundation\Http\FormRequest;
// Fachada de autorización.
use Illuminate\Support\Facades\Gate;
// Constructor de reglas avanzadas.
use Illuminate\Validation\Rule;
// Regla de contraseña segura de Laravel.
use Illuminate\Validation\Rules\Password;

// FORM REQUEST de usuarios del sistema (solo administrador): alta y edición.
class UsuarioRequest extends FormRequest
{
    // Rasgo con el método limpiarCampos().
    use LimpiaEntradas;

    // Paso 1: el Gate "gestionar-usuarios" (AppServiceProvider) solo deja pasar al administrador.
    public function authorize(): bool
    {
        // Lanza 403 si el usuario no es administrador.
        Gate::authorize('gestionar-usuarios');

        // Autorizado.
        return true;
    }

    // Paso 2: limpieza del nombre y del correo.
    protected function prepareForValidation(): void
    {
        // Quita etiquetas y espacios.
        $this->limpiarCampos(['name', 'email']);

        // El correo se guarda en minúsculas para evitar duplicados como Ana@x.com y ana@x.com.
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower($this->input('email'))]);
        }
    }

    /**
     * Paso 3: reglas de validación.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Usuario en edición (null en alta).
        $usuario = $this->route('usuario');

        // true cuando se edita un usuario existente.
        $esEdicion = $usuario instanceof User;

        return [
            // Nombre obligatorio.
            'name' => ['required', 'string', 'max:120'],
            // Correo obligatorio y único; en edición se ignora el propio registro.
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($esEdicion ? $usuario->id : null)],
            // Rol dentro de la lista permitida.
            'rol' => ['required', Rule::in(User::ROLES)],
            // Contraseña: obligatoria al crear, opcional al editar; mínimo 8 caracteres con letras y números.
            'password' => [$esEdicion ? 'nullable' : 'required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }
}
