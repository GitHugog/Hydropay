<?php

// Espacio de nombres de los listeners.
namespace App\Listeners;

// Evento que escucha.
use App\Events\PagoRegistrado;
// Notificación por correo.
use App\Notifications\PagoRecibido;
// Registro de eventos.
use Illuminate\Support\Facades\Log;
// Fachada de notificaciones.
use Illuminate\Support\Facades\Notification;
// Cualquier error de PHP.
use Throwable;

// LISTENER 2: envía al cliente un correo de confirmación de su pago (solo si tiene correo registrado).
class NotificarPagoAlCliente
{
    // Laravel llama a handle() automáticamente cuando ocurre PagoRegistrado.
    public function handle(PagoRegistrado $evento): void
    {
        // Cliente dueño del recibo pagado.
        $cliente = $evento->pago->cobro->cliente;

        // Los clientes sin correo simplemente no reciben aviso.
        if (! $cliente->email) {
            return;
        }

        try {
            // Notificación "bajo demanda": el cliente no es usuario del sistema, solo se usa su correo.
            Notification::route('mail', $cliente->email)
                ->notify(new PagoRecibido($evento->pago));
        } catch (Throwable $e) {
            // Un fallo del servidor de correo no debe cancelar el pago que ya se guardó.
            Log::error('No se pudo enviar la notificación de pago', [
                'pago_id' => $evento->pago->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
