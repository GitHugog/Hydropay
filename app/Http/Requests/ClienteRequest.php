<?php

// Espacio de nombres de los Form Requests.
namespace App\Http\Requests;

// Rasgo que limpia etiquetas HTML antes de validar.
use App\Http\Requests\Concerns\LimpiaEntradas;
// Modelo del cliente.
use App\Models\Cliente;
// Clase base de las solicitudes con validación.
use Illuminate\Foundation\Http\FormRequest;
// Fachada de autorización (ejecuta las Policies).
use Illuminate\Support\Facades\Gate;
// Constructor de reglas de validación avanzadas.
use Illuminate\Validation\Rule;

// FORM REQUEST de clientes: autoriza, limpia y valida el alta y la edición.
// Lo usan el controlador web (ClienteController) y el de la API (Api\ClienteController).
class ClienteRequest extends FormRequest
{
    // Rasgo con el método limpiarCampos().
    use LimpiaEntradas;

    // Paso 1: ¿el usuario puede hacer esta operación? (se revisa antes de validar).
    public function authorize(): bool
    {
        // Si la ruta trae un cliente ({cliente}) es una edición; si no, es un alta.
        $cliente = $this->route('cliente');

        // Gate::authorize lanza 403 con el mensaje de la Policy cuando el permiso se niega.
        if ($cliente instanceof Cliente) {
            Gate::authorize('update', $cliente);
        } else {
            Gate::authorize('create', Cliente::class);
        }

        // Si no se lanzó excepción, el usuario está autorizado.
        return true;
    }

    // Paso 2: limpieza de textos libres antes de aplicar las reglas.
    protected function prepareForValidation(): void
    {
        $this->limpiarCampos(['nombre', 'numero_cuenta', 'direccion', 'telefono', 'email']);
    }

    /**
     * Paso 3: reglas de validación.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Cliente en edición (null cuando es un alta).
        $cliente = $this->route('cliente');

        return [
            // Nombre obligatorio de hasta 120 caracteres.
            'nombre' => ['required', 'string', 'max:120'],
            // Cuenta obligatoria y única; en edición se ignora el propio registro.
            'numero_cuenta' => [
                'required', 'string', 'max:30',
                Rule::unique('clientes', 'numero_cuenta')->ignore($cliente instanceof Cliente ? $cliente->id : null),
            ],
            // Dirección obligatoria.
            'direccion' => ['required', 'string', 'max:255'],
            // Zona opcional; debe existir en el catálogo.
            'zona_id' => ['nullable', 'integer', Rule::exists('zonas', 'id')],
            // Teléfono opcional: dígitos, espacios, +, guiones y paréntesis (7 a 20 caracteres).
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9 +()\-]{7,20}$/'],
            // Correo opcional con formato válido.
            'email' => ['nullable', 'email', 'max:255'],
            // Estado del registro: verdadero o falso (el checkbox web puede no enviarse).
            'activo' => ['sometimes', 'boolean'],
            // Cuota del servicio: solo el administrador la asigna; a otros roles se les rechaza el campo.
            'periodicidad' => $this->esAdmin()
                ? ['sometimes', 'required', Rule::in(Cliente::PERIODICIDADES)]
                : ['prohibited'],
            'tarifa' => $this->esAdmin()
                ? ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99']
                : ['prohibited'],
        ];
    }

    // Indica si quien envía el formulario es administrador.
    private function esAdmin(): bool
    {
        return (bool) $this->user()?->esAdmin();
    }
}
