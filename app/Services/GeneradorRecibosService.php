<?php

// Espacio de nombres de los servicios.
namespace App\Services;

// Modelos que intervienen.
use App\Models\Cliente;
use App\Models\Cobro;
// Fechas inmutables (cada operación devuelve una fecha nueva sin modificar la original).
use Carbon\CarbonImmutable;
// Interfaz común de las fechas de Carbon.
use DateTimeInterface;
// Error de base de datos (índice único cliente + periodo).
use Illuminate\Database\QueryException;
// Registro de eventos.
use Illuminate\Support\Facades\Log;

// SERVICIO GENERADOR DE RECIBOS
// Crea el recibo del periodo actual a cada cliente activo con cuota asignada, según su periodicidad:
//   mensual   → "Noviembre 2026",           vence el último día del mes.
//   bimestral → "Noviembre-Diciembre 2026", vence el último día del bimestre (ene-feb, mar-abr, ...).
//   anual     → "Anual 2026",               vence el 31 de diciembre.
// Es seguro ejecutarlo varias veces: si el recibo del periodo ya existe, no se repite.
class GeneradorRecibosService
{
    /**
     * Calcula el periodo que corresponde a una fecha.
     *
     * @return array{etiqueta: string, inicio: CarbonImmutable, fin: CarbonImmutable}
     */
    public static function periodo(string $periodicidad, DateTimeInterface $fecha): array
    {
        // Fecha de referencia al inicio del día.
        $dia = CarbonImmutable::instance($fecha)->startOfDay();

        // Anual: del 1 de enero al 31 de diciembre.
        if ($periodicidad === Cliente::ANUAL) {
            return [
                'etiqueta' => 'Anual '.$dia->year,
                'inicio' => $dia->startOfYear(),
                'fin' => $dia->endOfYear()->startOfDay(),
            ];
        }

        // Bimestral: bimestres fijos que inician en mes impar (1, 3, 5, 7, 9, 11).
        if ($periodicidad === Cliente::BIMESTRAL) {
            // Mes con el que empieza el bimestre de la fecha (noviembre → 11, diciembre → 11).
            $mesInicio = $dia->month - (($dia->month - 1) % 2);
            $inicio = $dia->setDate($dia->year, $mesInicio, 1);

            return [
                'etiqueta' => ucfirst(nombre_mes($mesInicio)).'-'.ucfirst(nombre_mes($mesInicio + 1)).' '.$dia->year,
                'inicio' => $inicio,
                'fin' => $inicio->addMonthNoOverflow()->endOfMonth()->startOfDay(),
            ];
        }

        // Mensual (valor por defecto): el mes de la fecha.
        return [
            'etiqueta' => ucfirst(nombre_mes($dia->month)).' '.$dia->year,
            'inicio' => $dia->startOfMonth(),
            'fin' => $dia->endOfMonth()->startOfDay(),
        ];
    }

    // Genera los recibos del periodo actual. Con $cliente solo revisa a ese cliente.
    // Devuelve cuántos recibos nuevos se crearon.
    public function generar(?DateTimeInterface $fecha = null, ?Cliente $cliente = null): int
    {
        // Sin fecha se usa hoy.
        $fecha ??= now();

        // Clientes activos con cuota mayor a cero.
        $consulta = Cliente::query()
            ->where('activo', true)
            ->whereNotNull('tarifa')
            ->where('tarifa', '>', 0)
            ->when($cliente, fn ($q) => $q->whereKey($cliente->getKey()));

        // Contador de recibos creados.
        $creados = 0;

        // Recorrido por bloques de 200 (no carga todo el padrón en memoria).
        $consulta->lazyById(200)->each(function (Cliente $titular) use ($fecha, &$creados) {
            if ($this->generarPara($titular, $fecha)) {
                $creados++;
            }
        });

        // Bitácora de la ejecución.
        Log::info('Recibos generados automáticamente', ['creados' => $creados, 'cliente_id' => $cliente?->getKey()]);

        return $creados;
    }

    // Crea el recibo del periodo para un cliente; devuelve null si ya existía.
    public function generarPara(Cliente $cliente, DateTimeInterface $fecha): ?Cobro
    {
        // Periodo que corresponde según la periodicidad del cliente.
        $periodo = self::periodo($cliente->periodicidad ?: Cliente::MENSUAL, $fecha);

        // Si ya tiene recibo de ese periodo (automático o capturado a mano), no se repite.
        if ($cliente->cobros()->where('periodo', $periodo['etiqueta'])->exists()) {
            return null;
        }

        try {
            // Recibo con el importe de la cuota y fecha límite al final del periodo.
            $cobro = $cliente->cobros()->create([
                'periodo' => $periodo['etiqueta'],
                'monto' => $cliente->tarifa,
                'fecha_vencimiento' => $periodo['fin']->toDateString(),
                'estado' => Cobro::PENDIENTE,
                'descripcion' => 'Cuota '.($cliente->periodicidad ?: Cliente::MENSUAL).' del servicio de agua',
            ]);
        } catch (QueryException) {
            // El índice único (cliente_id, periodo) detectó que otra ejecución ya lo creó.
            return null;
        }

        // Ajusta el estado (vencido si la fecha límite ya pasó).
        $cobro->recalcularEstado();

        return $cobro;
    }
}
