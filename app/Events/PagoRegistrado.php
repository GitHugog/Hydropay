<?php

// Espacio de nombres de los eventos.
namespace App\Events;

// Modelo del pago.
use App\Models\Pago;
// Rasgo que permite disparar el evento con PagoRegistrado::dispatch().
use Illuminate\Foundation\Events\Dispatchable;
// Rasgo que serializa modelos si el evento se envía a una cola.
use Illuminate\Queue\SerializesModels;

// EVENTO: se dispara en RegistroPagoService cuando se guarda un pago nuevo.
// Los listeners de app/Listeners reaccionan a él (Laravel los descubre solos por el tipo del parámetro):
//   - ActualizarEstadoCobro  → marca el recibo como pagado.
//   - NotificarPagoAlCliente → envía el correo de confirmación.
class PagoRegistrado
{
    // Rasgos del evento.
    use Dispatchable, SerializesModels;

    // Recibe el pago recién guardado (propiedad pública accesible desde los listeners).
    public function __construct(public Pago $pago)
    {
    }
}
