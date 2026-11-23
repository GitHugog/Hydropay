<?php

// Fachada del programador de tareas de Laravel.
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| TAREAS PROGRAMADAS
|--------------------------------------------------------------------------
| Para que se ejecuten en desarrollo: php artisan schedule:work (en otra terminal).
| En un servidor: una tarea cron que ejecute "php artisan schedule:run" cada minuto.
*/

// Cada día a las 00:05 marca como vencidos los recibos sin pago cuya fecha límite ya pasó.
Schedule::command('hydropay:marcar-vencidos')->dailyAt('00:05');

// Cada día a las 00:10 crea el recibo del periodo actual a los clientes con cuota (mensual, bimestral o anual).
// Si el recibo ya existe no se repite; así, el primer día de cada periodo aparecen los recibos nuevos.
Schedule::command('hydropay:generar-recibos')->dailyAt('00:10');

// Cada día elimina de la base los tokens de la API que vencieron hace más de 24 horas (comando de Sanctum).
Schedule::command('sanctum:prune-expired --hours=24')->daily();
