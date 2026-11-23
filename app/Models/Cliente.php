<?php

// Espacio de nombres de los modelos Eloquent.
namespace App\Models;

// Servicio del panel; se usa para borrar su caché cuando cambian los datos.
use App\Services\ResumenService;
// Fábrica que genera clientes falsos.
use Database\Factories\ClienteFactory;
// Constructor de consultas de Eloquent (para tipar los scopes).
use Illuminate\Database\Eloquent\Builder;
// Rasgo que conecta el modelo con su fábrica.
use Illuminate\Database\Eloquent\Factories\HasFactory;
// Clase base de todos los modelos.
use Illuminate\Database\Eloquent\Model;
// Relación uno a muchos.
use Illuminate\Database\Eloquent\Relations\HasMany;
// Relación "a través de" otra tabla.
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

// Modelo de los titulares de una cuenta del servicio de agua (tabla "clientes").
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory;

    // Periodicidades de cobro que puede asignar el administrador.
    public const MENSUAL = 'mensual';
    public const BIMESTRAL = 'bimestral';
    public const ANUAL = 'anual';

    // Lista usada en la validación y en el formulario.
    public const PERIODICIDADES = [self::MENSUAL, self::BIMESTRAL, self::ANUAL];

    // Eventos del modelo: se ejecutan automáticamente al guardar o eliminar.
    protected static function booted(): void
    {
        // Al crear o modificar un cliente se borra el resumen guardado en caché del panel.
        static::saved(fn () => ResumenService::olvidar());
        // Al eliminar un cliente también se borra el resumen.
        static::deleted(fn () => ResumenService::olvidar());
    }

    // Campos permitidos para crear o actualizar un cliente (protección contra asignación masiva).
    protected $fillable = [
        'nombre',        // Nombre completo del titular.
        'numero_cuenta', // Número de cuenta único del servicio.
        'direccion',     // Domicilio donde se presta el servicio.
        'zona_id',       // Zona (municipio y localidad) a la que pertenece; opcional.
        'telefono',      // Teléfono de contacto (opcional).
        'email',         // Correo para avisos de pago (opcional).
        'activo',        // Indica si el registro del titular está habilitado.
        'periodicidad',  // Cada cuánto se le cobra: mensual, bimestral o anual.
        'tarifa',        // Importe de cada recibo (cuota del servicio); null = sin cuota.
    ];

    // Conversión automática de tipos al leer el modelo.
    protected $casts = [
        // En la base se guarda 1/0; en PHP se maneja como true/false.
        'activo' => 'boolean',
        // Importe con dos decimales.
        'tarifa' => 'decimal:2',
    ];

    // Indica si el cliente tiene una cuota asignada (y por lo tanto recibos automáticos).
    public function tieneCuota(): bool
    {
        return $this->tarifa !== null && (float) $this->tarifa > 0;
    }

    // Relación: el cliente pertenece a una zona (municipio y localidad); puede no tenerla.
    public function zona(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    // Relación: un cliente puede tener muchos recibos (cobros).
    public function cobros(): HasMany
    {
        return $this->hasMany(Cobro::class);
    }

    // Relación: un cliente tiene muchos pagos a través de sus recibos (clientes → cobros → pagos).
    public function pagos(): HasManyThrough
    {
        return $this->hasManyThrough(Pago::class, Cobro::class);
    }

    // Suma de los recibos que todavía debe el cliente (sin pago y no cancelados).
    public function adeudo(): float
    {
        // Se usa el scope porPagar del modelo Cobro y se suma la columna monto.
        return round((float) $this->cobros()->porPagar()->sum('monto'), 2);
    }

    // Scope reutilizable: Cliente::buscar('texto') filtra por nombre, cuenta o dirección.
    public function scopeBuscar(Builder $consulta, ?string $texto): Builder
    {
        // when() solo agrega el filtro si el texto no está vacío.
        return $consulta->when($texto, function (Builder $q, string $texto) {
            // Se agrupan los OR entre paréntesis para no afectar otros filtros.
            $q->where(function (Builder $sub) use ($texto) {
                // LIKE con comodines busca coincidencias parciales.
                $sub->where('nombre', 'like', "%{$texto}%")
                    ->orWhere('numero_cuenta', 'like', "%{$texto}%")
                    ->orWhere('direccion', 'like', "%{$texto}%");
            });
        });
    }
}
