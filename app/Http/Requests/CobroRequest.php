<?php

// Espacio de nombres de los Form Requests.
namespace App\Http\Requests;

// Rasgo que limpia etiquetas HTML antes de validar.
use App\Http\Requests\Concerns\LimpiaEntradas;
// Modelo del recibo.
use App\Models\Cobro;
// Clase base de las solicitudes con validación.
use Illuminate\Foundation\Http\FormRequest;
// Fachada de autorización.
use Illuminate\Support\Facades\Gate;
// Constructor de reglas avanzadas.
use Illuminate\Validation\Rule;

// FORM REQUEST de recibos (cobros): autoriza, limpia y valida el alta y la edición.
// Lo usan CobroController (web) y Api\CobroController.
class CobroRequest extends FormRequest
{
    // Rasgo con el método limpiarCampos().
    use LimpiaEntradas;

    // Paso 1: autorización (antes de validar).
    public function authorize(): bool
    {
        // Recibo en edición; null cuando se trata de un alta.
        $cobro = $this->route('cobro');

        // Alta: basta con que el rol pueda registrar.
        if (! $cobro instanceof Cobro) {
            Gate::authorize('create', Cobro::class);

            return true;
        }

        // Edición: CobroPolicy revisa rol, que no esté pagado y que no esté cancelado (salvo admin).
        Gate::authorize('update', $cobro);

        // Cancelar un recibo vigente requiere además el permiso "cancelar" (solo administrador).
        if ($this->input('estado') === Cobro::CANCELADO && $cobro->estado !== Cobro::CANCELADO) {
            Gate::authorize('cancelar', $cobro);
        }

        // Si ninguna revisión lanzó 403, la operación está autorizada.
        return true;
    }

    // Paso 2: limpieza de textos libres.
    protected function prepareForValidation(): void
    {
        $this->limpiarCampos(['periodo', 'descripcion']);
    }

    /**
     * Paso 3: reglas de validación.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Recibo en edición (null en alta).
        $cobro = $this->route('cobro');

        // true cuando se edita un recibo existente.
        $esEdicion = $cobro instanceof Cobro;

        // El cliente debe existir; en un alta además debe estar activo.
        $reglaCliente = Rule::exists('clientes', 'id');
        if (! $esEdicion) {
            $reglaCliente = $reglaCliente->where('activo', true);
        }

        // Reglas comunes al alta y a la edición.
        $reglas = [
            // Cliente obligatorio, numérico y existente.
            'cliente_id' => ['required', 'integer', $reglaCliente],
            // Periodo obligatorio y único para el mismo cliente (no se emiten dos recibos del mismo periodo).
            'periodo' => [
                'required', 'string', 'max:30',
                Rule::unique('cobros', 'periodo')
                    ->where('cliente_id', $this->input('cliente_id'))
                    ->ignore($esEdicion ? $cobro->id : null),
            ],
            // Importe mayor a cero con máximo dos decimales.
            'monto' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            // Fecha límite opcional.
            'fecha_vencimiento' => ['nullable', 'date'],
            // Observaciones opcionales.
            'descripcion' => ['nullable', 'string', 'max:500'],
        ];

        // En edición se elige el estado: solo "pendiente" o "cancelado" (pagado y vencido se calculan solos).
        if ($esEdicion) {
            $reglas['estado'] = ['required', Rule::in([Cobro::PENDIENTE, Cobro::CANCELADO])];
        }

        // Reglas finales.
        return $reglas;
    }
}
