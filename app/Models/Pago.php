<?php

// Espacio de nombres de los modelos Eloquent.
namespace App\Models;

// Servicio del panel; se usa para borrar su caché cuando se registra un pago.
use App\Services\ResumenService;
// Fábrica que genera pagos falsos.
use Database\Factories\PagoFactory;
// Constructor de consultas (para tipar el scope).
use Illuminate\Database\Eloquent\Builder;
// Rasgo que conecta el modelo con su fábrica.
use Illuminate\Database\Eloquent\Factories\HasFactory;
// Clase base de los modelos.
use Illuminate\Database\Eloquent\Model;
// Relación "pertenece a".
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Modelo de los pagos completos que liquidan un recibo (tabla "pagos").
class Pago extends Model
{
    /** @use HasFactory<PagoFactory> */
    use HasFactory;

    // Métodos de pago aceptados por el organismo.
    public const METODOS = ['efectivo', 'transferencia', 'tarjeta', 'deposito'];

    // Eventos del modelo: al guardar o eliminar un pago se borra el resumen en caché.
    protected static function booted(): void
    {
        // INTEGRIDAD: al crearse un pago (por la interfaz, la API, un seeder o tinker) su recibo queda "pagado".
        static::created(fn (Pago $pago) => $pago->cobro?->recalcularEstado());

        // Un pago nuevo cambia el total cobrado del panel.
        static::saved(fn () => ResumenService::olvidar());
        // Se mantiene por consistencia (la interfaz no elimina pagos).
        static::deleted(fn () => ResumenService::olvidar());
    }

    // Campos que se pueden asignar con create().
    protected $fillable = [
        'cobro_id',    // Recibo que se liquida.
        'user_id',     // Usuario del sistema que registró el pago.
        'monto',       // Importe (siempre igual al monto del recibo).
        'metodo',      // efectivo, transferencia, tarjeta o deposito.
        'referencia',  // Folio o referencia bancaria (opcional).
        'fecha_pago',  // Fecha en que se recibió el dinero.
        'comprobante', // Ruta privada del archivo comprobante (opcional).
    ];

    // Conversión de tipos al leer el modelo.
    protected $casts = [
        // Importe con dos decimales.
        'monto' => 'decimal:2',
        // Fecha del pago como objeto Carbon.
        'fecha_pago' => 'date',
    ];

    // Relación: cada pago pertenece a un recibo.
    public function cobro(): BelongsTo
    {
        return $this->belongsTo(Cobro::class);
    }

    // Relación: usuario del sistema que registró el pago (columna user_id).
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scope: búsqueda por referencia, nombre o cuenta del cliente y filtro por método de pago.
    public function scopeFiltrar(Builder $consulta, ?string $texto, ?string $metodo): Builder
    {
        return $consulta
            // Filtro de texto (solo si se escribió algo).
            ->when($texto, function (Builder $q, string $texto) {
                // Paréntesis para agrupar las condiciones OR.
                $q->where(function (Builder $sub) use ($texto) {
                    // Coincidencia en la referencia del pago.
                    $sub->where('referencia', 'like', "%{$texto}%")
                        // O en el nombre/cuenta del cliente (pagos → cobro → cliente).
                        ->orWhereHas('cobro.cliente', function (Builder $c) use ($texto) {
                            $c->where('nombre', 'like', "%{$texto}%")
                                ->orWhere('numero_cuenta', 'like', "%{$texto}%");
                        });
                });
            })
            // Filtro exacto por método de pago.
            ->when($metodo, fn (Builder $q, string $metodo) => $q->where('metodo', $metodo));
    }
}
