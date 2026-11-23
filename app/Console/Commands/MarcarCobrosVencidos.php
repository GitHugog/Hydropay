<?php

// Espacio de nombres de los comandos de consola.
namespace App\Console\Commands;

// Modelo del recibo.
use App\Models\Cobro;
// Clase base de los comandos Artisan.
use Illuminate\Console\Command;
// Registro de eventos.
use Illuminate\Support\Facades\Log;

// COMANDO ARTISAN: php artisan hydropay:marcar-vencidos
// Cambia a "vencido" los recibos pendientes cuya fecha límite ya pasó y que no tienen pago.
// Se programa a diario en routes/console.php.
class MarcarCobrosVencidos extends Command
{
    // Nombre con el que se ejecuta en la terminal.
    protected $signature = 'hydropay:marcar-vencidos';

    // Descripción que aparece en "php artisan list".
    protected $description = 'Marca como vencidos los recibos pendientes con fecha límite pasada y sin pago';

    // Lógica del comando.
    public function handle(): int
    {
        // Contadores para el resumen final.
        $revisados = 0;
        $vencidos = 0;

        // lazyById recorre los registros por bloques usando el ID; así no se salta filas
        // aunque el estado cambie durante el recorrido (problema que sí tiene chunk()).
        Cobro::query()
            ->where('estado', Cobro::PENDIENTE)                    // Solo recibos sin pagar.
            ->whereDate('fecha_vencimiento', '<', today())         // Con fecha límite anterior a hoy.
            ->lazyById(200)                                        // Bloques de 200 registros.
            ->each(function (Cobro $cobro) use (&$revisados, &$vencidos) {
                // Cuenta el recibo revisado.
                $revisados++;

                // recalcularEstado cambia a vencido solo si no existe un pago.
                $cobro->recalcularEstado();

                // Cuenta los que quedaron vencidos.
                if ($cobro->estado === Cobro::VENCIDO) {
                    $vencidos++;
                }
            });

        // Bitácora de la ejecución (útil para comprobar que el programador funciona).
        Log::info('Comando hydropay:marcar-vencidos ejecutado', ['revisados' => $revisados, 'vencidos' => $vencidos]);

        // Mensaje en la terminal.
        $this->info("Recibos revisados: {$revisados}. Marcados como vencidos: {$vencidos}.");

        // Código de salida 0 = éxito.
        return self::SUCCESS;
    }
}
