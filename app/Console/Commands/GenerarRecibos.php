<?php

// Espacio de nombres de los comandos de consola.
namespace App\Console\Commands;

// Servicio que crea los recibos.
use App\Services\GeneradorRecibosService;
// Clase base de los comandos Artisan.
use Illuminate\Console\Command;
// Fechas.
use Illuminate\Support\Carbon;
// Error al interpretar una fecha mal escrita.
use Throwable;

// COMANDO ARTISAN: php artisan hydropay:generar-recibos [--fecha=AAAA-MM-DD]
// Crea el recibo del periodo actual a cada cliente activo con cuota (mensual, bimestral o anual).
// Se programa a diario en routes/console.php; al iniciar cada periodo aparecen los recibos nuevos.
class GenerarRecibos extends Command
{
    // Nombre y opción --fecha (útil para pruebas o para generar un periodo específico).
    protected $signature = 'hydropay:generar-recibos {--fecha= : Fecha de referencia AAAA-MM-DD (por defecto, hoy)}';

    // Descripción que aparece en "php artisan list".
    protected $description = 'Genera el recibo del periodo actual a los clientes activos con cuota asignada';

    // Lógica del comando; el servicio se recibe por inyección de dependencias.
    public function handle(GeneradorRecibosService $generador): int
    {
        // Fecha indicada en la opción o la de hoy.
        try {
            $fecha = $this->option('fecha') ? Carbon::parse($this->option('fecha')) : now();
        } catch (Throwable) {
            $this->error('La fecha debe tener el formato AAAA-MM-DD.');

            return self::INVALID;
        }

        // Genera los recibos que falten.
        $creados = $generador->generar($fecha);

        // Resumen en la terminal.
        $this->info("Recibos generados: {$creados} (referencia {$fecha->format('d/m/Y')}).");

        return self::SUCCESS;
    }
}
