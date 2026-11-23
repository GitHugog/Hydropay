<?php

// Espacio de nombres de los modelos Eloquent.
namespace App\Models;

// Servicio del panel; se usa para borrar su caché cuando cambian los recibos.
use App\Services\ResumenService;
// Fábrica que genera recibos falsos.
use Database\Factories\CobroFactory;
// Constructor de consultas (para tipar los scopes).
use Illuminate\Database\Eloquent\Builder;
// Rasgo que conecta el modelo con su fábrica.
use Illuminate\Database\Eloquent\Factories\HasFactory;
// Clase base de los modelos.
use Illuminate\Database\Eloquent\Model;
// Relación "pertenece a".
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// Relación uno a uno.
use Illuminate\Database\Eloquent\Relations\HasOne;

// Modelo de los recibos o adeudos que el organismo operador registra para cada cliente (tabla "cobros").
class Cobro extends Model
{
    /** @use HasFactory<CobroFactory> */
    use HasFactory;

    // Existe una deuda y todavía está dentro del plazo (en pantalla se muestra "Sin pagar").
    public const PENDIENTE = 'pendiente';

    // El importe completo ya fue recibido.
    public const PAGADO = 'pagado';

    // Pasó la fecha límite y no existe un pago.
    public const VENCIDO = 'vencido';

    // El recibo fue anulado por un administrador y no admite pagos.
    public const CANCELADO = 'cancelado';

    // Lista de todos los estados válidos (para filtros y validaciones).
    public const ESTADOS = [self::PENDIENTE, self::PAGADO, self::VENCIDO, self::CANCELADO];

    // Eventos del modelo: se ejecutan al guardar o eliminar un recibo.
    protected static function booted(): void
    {
        // Cualquier cambio en recibos invalida el resumen en caché del panel.
        static::saved(fn () => ResumenService::olvidar());
        // Lo mismo al eliminar un recibo.
        static::deleted(fn () => ResumenService::olvidar());
    }

    // Campos que se pueden asignar con create() o update().
    protected $fillable = [
        'cliente_id',        // Cliente al que pertenece el recibo.
        'periodo',           // Periodo o concepto (ej. "Noviembre 2026").
        'monto',             // Importe total a pagar.
        'fecha_vencimiento', // Fecha límite de pago (opcional).
        'estado',            // pendiente, pagado, vencido o cancelado.
        'descripcion',       // Observaciones (opcional).
    ];

    // Conversión de tipos al leer el modelo.
    protected $casts = [
        // El importe siempre se maneja con dos decimales.
        'monto' => 'decimal:2',
        // La fecha límite se convierte en objeto Carbon para compararla y darle formato.
        'fecha_vencimiento' => 'date',
    ];

    // Relación: cada recibo pertenece a un solo cliente.
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    // Relación: REGLA DE HYDROPAY, cada recibo admite como máximo un pago completo.
    public function pago(): HasOne
    {
        return $this->hasOne(Pago::class);
    }

    // Indica si el recibo ya tiene pago; usa la relación cargada para no repetir consultas.
    public function tienePago(): bool
    {
        // Si la relación ya viene precargada (with('pago')) no se consulta la base otra vez.
        if ($this->relationLoaded('pago')) {
            return $this->getRelation('pago') !== null;
        }

        // Si no está cargada, se pregunta a la base de datos si existe el pago.
        return $this->pago()->exists();
    }

    // Importe recibido: el monto del pago o cero si todavía no se paga.
    public function totalPagado(): float
    {
        // Usa la relación cargada o la consulta en ese momento.
        $pago = $this->relationLoaded('pago') ? $this->getRelation('pago') : $this->pago()->first();

        // Convierte el decimal a número flotante.
        return $pago ? (float) $pago->monto : 0.0;
    }

    // Importe pendiente: completo mientras no exista pago; cero si está pagado o cancelado.
    public function saldo(): float
    {
        // Un recibo cancelado no representa deuda.
        if ($this->estado === self::CANCELADO) {
            return 0.0;
        }

        // Si ya existe el pago completo, no queda saldo; si no, se debe el monto total.
        return $this->tienePago() ? 0.0 : (float) $this->monto;
    }

    // Recalcula el estado según el pago y la fecha límite, y lo guarda solo si cambió.
    public function recalcularEstado(): void
    {
        // Un recibo cancelado conserva ese estado (solo un administrador puede reactivarlo).
        if ($this->estado === self::CANCELADO) {
            return;
        }

        // Se consulta la base directamente para tener el dato más reciente (importante con varios cajeros).
        if ($this->pago()->exists()) {
            // Con pago registrado el recibo queda liquidado.
            $nuevoEstado = self::PAGADO;
        } elseif ($this->fecha_vencimiento && $this->fecha_vencimiento->lt(today())) {
            // Sin pago y con la fecha límite ya pasada, el recibo está vencido.
            $nuevoEstado = self::VENCIDO;
        } else {
            // Sin pago y dentro del plazo (o sin fecha límite), sigue pendiente.
            $nuevoEstado = self::PENDIENTE;
        }

        // Solo se escribe en la base cuando el estado realmente cambió.
        if ($nuevoEstado !== $this->estado) {
            $this->update(['estado' => $nuevoEstado]);
        }
    }

    // Scope: recibos que todavía se deben (pendientes o vencidos y sin pago).
    public function scopePorPagar(Builder $consulta): Builder
    {
        return $consulta
            // Solo estados que representan deuda.
            ->whereIn('estado', [self::PENDIENTE, self::VENCIDO])
            // Y que no tengan un pago relacionado.
            ->whereDoesntHave('pago');
    }

    // Scope: búsqueda por periodo, nombre o cuenta del cliente y filtro por estado.
    public function scopeFiltrar(Builder $consulta, ?string $texto, ?string $estado): Builder
    {
        return $consulta
            // Filtro de texto (solo si se escribió algo).
            ->when($texto, function (Builder $q, string $texto) {
                // Paréntesis para agrupar las condiciones OR.
                $q->where(function (Builder $sub) use ($texto) {
                    // Coincidencia en el periodo del recibo.
                    $sub->where('periodo', 'like', "%{$texto}%")
                        // O coincidencia en el nombre o la cuenta del cliente relacionado.
                        ->orWhereHas('cliente', function (Builder $c) use ($texto) {
                            $c->where('nombre', 'like', "%{$texto}%")
                                ->orWhere('numero_cuenta', 'like', "%{$texto}%");
                        });
                });
            })
            // Filtro exacto por estado (solo si se eligió uno).
            ->when($estado, fn (Builder $q, string $estado) => $q->where('estado', $estado));
    }
}
