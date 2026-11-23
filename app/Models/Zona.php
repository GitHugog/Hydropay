<?php

// Espacio de nombres de los modelos.
namespace App\Models;

// Fábrica de datos de prueba.
use Illuminate\Database\Eloquent\Factories\HasFactory;
// Clase base de Eloquent.
use Illuminate\Database\Eloquent\Model;
// Tipo de la relación uno a muchos.
use Illuminate\Database\Eloquent\Relations\HasMany;
// Constructor de consultas para los scopes.
use Illuminate\Database\Eloquent\Builder;

// MODELO ZONA: municipio y localidad (pueblo, colonia o ranchería) donde vive el cliente.
// Tabla: zonas. Solo el administrador la administra.
class Zona extends Model
{
    // Habilita Zona::factory() en pruebas y seeders.
    use HasFactory;

    // Campos que se pueden asignar en bloque desde el formulario.
    protected $fillable = [
        'municipio',  // Municipio (por ejemplo, "Tala").
        'localidad',  // Pueblo o localidad (por ejemplo, "Cuisillos").
    ];

    // Relación: una zona tiene muchos clientes.
    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    // Texto para mostrar: "Cuisillos, Tala".
    public function nombreCompleto(): string
    {
        return "{$this->localidad}, {$this->municipio}";
    }

    // Scope: orden por municipio y luego por localidad (Zona::ordenadas()).
    public function scopeOrdenadas(Builder $consulta): Builder
    {
        return $consulta->orderBy('municipio')->orderBy('localidad');
    }
}
