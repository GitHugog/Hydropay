<?php

// Espacio de nombres de los API Resources.
namespace App\Http\Resources;

// Solicitud HTTP.
use Illuminate\Http\Request;
// Clase base de los Resources.
use Illuminate\Http\Resources\Json\JsonResource;

// Representación JSON de un pago.
class PagoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,                                                    // Folio del pago.
            'cobro_id' => $this->cobro_id,                                        // Recibo liquidado.
            'cobro' => $this->whenLoaded('cobro', fn ($cobro) => new CobroResource($cobro)), // Recibo (si se cargó).
            'monto' => (float) $this->monto,                                      // Importe recibido.
            'metodo' => $this->metodo,                                            // Método de pago.
            'referencia' => $this->referencia,                                    // Folio bancario o null.
            'fecha_pago' => $this->fecha_pago?->format('Y-m-d'),                  // Fecha de recepción.
            'tiene_comprobante' => (bool) $this->comprobante,                     // No se expone la ruta privada.
            'registrado_por' => $this->whenLoaded('registradoPor', fn ($usuario) => new UsuarioResource($usuario)), // Usuario que lo registró (null si se eliminó).
            'creado_en' => $this->created_at?->toIso8601String(),                 // Momento del registro.
        ];
    }
}
