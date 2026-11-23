<?php

// Espacio de nombres de los API Resources (transforman modelos a JSON).
namespace App\Http\Resources;

// Solicitud HTTP.
use Illuminate\Http\Request;
// Clase base de los Resources.
use Illuminate\Http\Resources\Json\JsonResource;

// Representación JSON de un usuario del sistema (nunca incluye la contraseña).
class UsuarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,         // Identificador.
            'nombre' => $this->name,   // Nombre visible.
            'email' => $this->email,   // Correo de acceso.
            'rol' => $this->rol,       // admin, cajero o consulta.
        ];
    }
}
