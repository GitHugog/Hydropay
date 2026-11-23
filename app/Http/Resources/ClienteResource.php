<?php

// Espacio de nombres de los API Resources.
namespace App\Http\Resources;

// Solicitud HTTP.
use Illuminate\Http\Request;
// Clase base de los Resources.
use Illuminate\Http\Resources\Json\JsonResource;

// Representación JSON de un cliente.
class ClienteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,                                     // Identificador.
            'numero_cuenta' => $this->numero_cuenta,               // Cuenta única del servicio.
            'nombre' => $this->nombre,                             // Titular.
            'direccion' => $this->direccion,                       // Domicilio.
            'telefono' => $this->telefono,                         // Teléfono o null.
            'email' => $this->email,                               // Correo o null.
            'activo' => (bool) $this->activo,                      // Estado del registro.
            'periodicidad' => $this->periodicidad,                 // mensual, bimestral o anual.
            'tarifa' => $this->tarifa !== null ? (float) $this->tarifa : null, // Cuota por periodo o null.
            'zona_id' => $this->zona_id,                           // Id de la zona o null.
            // Zona completa solo si se cargó la relación (y existe).
            'zona' => $this->whenLoaded('zona', fn ($zona) => $zona ? [
                'id' => $zona->id,
                'municipio' => $zona->municipio,
                'localidad' => $zona->localidad,
            ] : null),
            'recibos' => $this->whenCounted('cobros'),             // Total de recibos (solo si se contó con withCount).
            'creado_en' => $this->created_at?->toIso8601String(),  // Fecha de alta en formato ISO 8601.
            'actualizado_en' => $this->updated_at?->toIso8601String(), // Última modificación.
        ];
    }
}
