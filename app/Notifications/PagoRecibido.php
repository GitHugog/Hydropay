<?php

// Espacio de nombres de las notificaciones.
namespace App\Notifications;

// Modelo del pago.
use App\Models\Pago;
// Constructor del mensaje de correo.
use Illuminate\Notifications\Messages\MailMessage;
// Clase base de las notificaciones.
use Illuminate\Notifications\Notification;

// NOTIFICACIÓN POR CORREO (Mail + Notifications): confirma al cliente que su pago fue recibido.
// Con MAIL_MAILER=log el correo se escribe en storage/logs/laravel.log en lugar de enviarse.
class PagoRecibido extends Notification
{
    // Recibe el pago confirmado.
    public function __construct(public Pago $pago)
    {
    }

    /**
     * Canales de envío.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Solo correo electrónico.
        return ['mail'];
    }

    // Contenido del correo.
    public function toMail(object $notifiable): MailMessage
    {
        // Recibo actualizado con su cliente (consulta fresca para mostrar el estado final).
        $cobro = $this->pago->cobro()->with('cliente')->first();

        // Mensaje con asunto, saludo y líneas de texto.
        return (new MailMessage)
            ->subject('Pago recibido - '.config('app.name'))                                      // Asunto.
            ->greeting('Hola, '.$cobro->cliente->nombre)                                         // Saludo.
            ->line('Registramos tu pago de '.formato_moneda($this->pago->monto)                  // Importe.
                .' correspondiente al periodo '.$cobro->periodo.'.')                             // Periodo.
            ->line('Cuenta: '.$cobro->cliente->numero_cuenta)                                    // Cuenta.
            ->line('Método: '.ucfirst($this->pago->metodo).' · Folio de pago: '.$this->pago->id) // Método y folio.
            ->line('El recibo quedó liquidado por completo.')                                    // Estado.
            ->salutation('Gracias por tu pago.');                                                // Despedida.
    }
}
