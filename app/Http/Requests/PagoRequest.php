<?php

// Espacio de nombres de los Form Requests.
namespace App\Http\Requests;

// Rasgo que limpia etiquetas HTML antes de validar.
use App\Http\Requests\Concerns\LimpiaEntradas;
// Modelo del pago.
use App\Models\Pago;
// Clase base de las solicitudes con validación.
use Illuminate\Foundation\Http\FormRequest;
// Fachada de autorización.
use Illuminate\Support\Facades\Gate;
// Constructor de reglas avanzadas.
use Illuminate\Validation\Rule;

// FORM REQUEST de pagos: autoriza y valida el registro de un pago completo.
// El importe NO se captura: el servicio RegistroPagoService lo toma del recibo guardado en la base.
class PagoRequest extends FormRequest
{
    // Rasgo con el método limpiarCampos().
    use LimpiaEntradas;

    // Paso 1: solo admin y cajero registran pagos (PagoPolicy → PoliticaBase::create).
    public function authorize(): bool
    {
        // Lanza 403 con mensaje si el rol es de consulta.
        Gate::authorize('create', Pago::class);

        // Autorizado.
        return true;
    }

    // Paso 2: limpieza del único texto libre.
    protected function prepareForValidation(): void
    {
        $this->limpiarCampos(['referencia']);
    }

    /**
     * Paso 3: reglas de validación.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Recibo obligatorio y existente.
            'cobro_id' => ['required', 'integer', 'exists:cobros,id'],
            // Método dentro de la lista permitida.
            'metodo' => ['required', Rule::in(Pago::METODOS)],
            // Referencia opcional (folio, número de operación).
            'referencia' => ['nullable', 'string', 'max:100'],
            // Fecha obligatoria y no futura.
            'fecha_pago' => ['required', 'date', 'before_or_equal:today'],
            // Comprobante opcional: JPG, PNG o PDF de máximo 2 MB.
            'comprobante' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            // Si alguien envía un monto manipulando el formulario o la API, se valida y luego se compara con el recibo.
            'monto' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
        ];
    }
}
