<?php

// Espacio de nombres de los listeners.
namespace App\Listeners;

// Evento que escucha.
use App\Events\PagoRegistrado;
// Registro de eventos.
use Illuminate\Support\Facades\Log;

// LISTENER 1: después de registrar el pago, recalcula el estado del recibo (queda "pagado").
class ActualizarEstadoCobro
{
    // Laravel llama a handle() automáticamente cuando ocurre PagoRegistrado.
    public function handle(PagoRegistrado $evento): void
    {
        // Recibo relacionado con el pago.
        $cobro = $evento->pago->cobro;

        // Con el pago existente, el estado cambia a "pagado".
        $cobro->recalcularEstado();

        // Bitácora del cambio.
        Log::info('Estado del cobro actualizado tras un pago', [
            'cobro_id' => $cobro->id,
            'estado' => $cobro->estado,
        ]);
    }
}
