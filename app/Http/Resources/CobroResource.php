<?php

// Espacio de nombres de los API Resources.
namespace App\Http\Resources;

// Modelo del recibo (para sus constantes).
use App\Models\Cobro;
// Solicitud HTTP.
use Illuminate\Http\Request;
// Clase base de los Resources.
use Illuminate\Http\Resources\Json\JsonResource;

// Representación JSON de un recibo (cobro).
class CobroResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,                                                   // Identificador.
            'cliente_id' => $this->cliente_id,                                   // Cliente dueño del recibo.
            'cliente' => $this->whenLoaded('cliente', fn ($cliente) => new ClienteResource($cliente)), // Cliente (si se cargó).
            'periodo' => $this->periodo,                                         // Periodo o concepto.
            'monto' => (float) $this->monto,                                     // Importe total.
            'fecha_vencimiento' => $this->fecha_vencimiento?->format('Y-m-d'),   // Fecha límite o null.
            'estado' => $this->estado,                                           // Valor interno.
            'estado_texto' => etiqueta_estado($this->estado),                    // Texto visible ("Sin pagar"...).
            'saldo' => $this->when(                                              // Importe pendiente (solo si se cargó el pago).
                $this->relationLoaded('pago') || $this->estado === Cobro::CANCELADO,
                fn () => $this->saldo()
            ),
            'descripcion' => $this->descripcion,                                 // Observaciones.
            'pago' => $this->whenLoaded('pago', fn ($pago) => new PagoResource($pago)), // Pago: objeto, null si no hay, u omitido si no se cargó.
            'creado_en' => $this->created_at?->toIso8601String(),                // Fecha de registro.
            'actualizado_en' => $this->updated_at?->toIso8601String(),           // Última modificación.
        ];
    }
}
