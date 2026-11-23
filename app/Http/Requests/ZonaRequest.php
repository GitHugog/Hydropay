<?php

// Espacio de nombres de los Form Requests.
namespace App\Http\Requests;

// Rasgo que limpia etiquetas HTML antes de validar.
use App\Http\Requests\Concerns\LimpiaEntradas;
// Modelo de la zona.
use App\Models\Zona;
// Clase base de las solicitudes con validación.
use Illuminate\Foundation\Http\FormRequest;
// Fachada de autorización.
use Illuminate\Support\Facades\Gate;
// Constructor de reglas avanzadas.
use Illuminate\Validation\Rule;

// FORM REQUEST de zonas (solo administrador): alta y edición.
class ZonaRequest extends FormRequest
{
    // Rasgo con el método limpiarCampos().
    use LimpiaEntradas;

    // Paso 1: solo el administrador (Gate "administrar-catalogos" en AppServiceProvider).
    public function authorize(): bool
    {
        Gate::authorize('administrar-catalogos');

        return true;
    }

    // Paso 2: quita etiquetas HTML y espacios sobrantes.
    protected function prepareForValidation(): void
    {
        $this->limpiarCampos(['municipio', 'localidad']);
    }

    /**
     * Paso 3: reglas de validación.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Zona en edición (null en alta).
        $zona = $this->route('zona');

        return [
            // Municipio obligatorio.
            'municipio' => ['required', 'string', 'max:120'],
            // Localidad obligatoria y única dentro del mismo municipio (se ignora la propia al editar).
            'localidad' => [
                'required', 'string', 'max:120',
                Rule::unique('zonas', 'localidad')
                    ->where(fn ($q) => $q->where('municipio', $this->input('municipio')))
                    ->ignore($zona instanceof Zona ? $zona->id : null),
            ],
        ];
    }
}
