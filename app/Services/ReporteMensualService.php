<?php

// Espacio de nombres de los servicios.
namespace App\Services;

// Modelos que alimentan el reporte.
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Pago;
// Manejo de fechas.
use Illuminate\Support\Carbon;

// SERVICIO DE REPORTES MENSUALES.
// Calcula, para un mes y año: ingresos del mes, recibos emitidos por estado, adeudo vigente,
// principales deudores y clientes registrados. Usa COLLECTIONS para agrupar y sumar.
class ReporteMensualService
{
    /**
     * Genera los datos del reporte del mes indicado.
     *
     * @return array<string, mixed>
     */
    public function generar(int $anio, int $mes): array
    {
        // Primer día del mes a las 00:00:00.
        $inicio = Carbon::create($anio, $mes, 1)->startOfDay();

        // Último día del mes a las 23:59:59.
        $fin = $inicio->copy()->endOfMonth();

        // ---- 1. Ingresos: pagos recibidos dentro del mes ----
        // whereDate compara solo la fecha (la columna se guarda con hora 00:00:00).
        $pagos = Pago::query()
            ->with(['cobro.cliente', 'registradoPor'])          // Relaciones para el detalle y el CSV.
            ->whereDate('fecha_pago', '>=', $inicio->toDateString())
            ->whereDate('fecha_pago', '<=', $fin->toDateString())
            ->orderBy('fecha_pago')                              // Orden cronológico.
            ->orderBy('id')
            ->get();

        // Collection: cantidad e importe por cada método de pago (incluye métodos sin movimientos).
        $porMetodo = collect(Pago::METODOS)->mapWithKeys(fn (string $metodo) => [
            $metodo => [
                'cantidad' => $pagos->where('metodo', $metodo)->count(),
                'total' => round((float) $pagos->where('metodo', $metodo)->sum('monto'), 2),
            ],
        ]);

        // ---- 2. Recibos emitidos en el mes (según su fecha de registro) ----
        $recibos = Cobro::query()
            ->whereBetween('created_at', [$inicio, $fin])       // Registrados entre el inicio y el fin del mes.
            ->get(['id', 'estado', 'monto']);

        // Collection: cantidad e importe por estado del recibo.
        $porEstado = collect(Cobro::ESTADOS)->mapWithKeys(fn (string $estado) => [
            $estado => [
                'cantidad' => $recibos->where('estado', $estado)->count(),
                'total' => round((float) $recibos->where('estado', $estado)->sum('monto'), 2),
            ],
        ]);

        // ---- 3. Adeudo vigente (cartera): todo lo que se debe hoy, sin importar el mes de emisión ----
        $cartera = Cobro::query()->porPagar()->get(['id', 'cliente_id', 'estado', 'monto']);

        // ---- 4. Principales deudores con recibos vencidos (para dar seguimiento) ----
        $deudores = $cartera
            ->where('estado', Cobro::VENCIDO)                    // Solo lo vencido.
            ->groupBy('cliente_id')                              // Un grupo por cliente.
            ->map(fn ($grupo, $clienteId) => [                   // Resumen de cada grupo.
                'cliente_id' => (int) $clienteId,
                'recibos' => $grupo->count(),
                'adeudo' => round((float) $grupo->sum('monto'), 2),
            ])
            ->sortByDesc('adeudo')                               // Mayor adeudo primero.
            ->take(10)                                           // Solo los diez principales.
            ->values();                                          // Reinicia los índices.

        // Se cargan los datos de esos clientes en una sola consulta y se agregan al resumen.
        $clientes = Cliente::query()->whereIn('id', $deudores->pluck('cliente_id'))->get(['id', 'nombre', 'numero_cuenta'])->keyBy('id');
        $deudores = $deudores->map(fn (array $fila) => $fila + ['cliente' => $clientes->get($fila['cliente_id'])]);

        // ---- Resultado final del reporte ----
        return [
            'anio' => $anio,                                                     // Año consultado.
            'mes' => $mes,                                                       // Mes consultado (1-12).
            'titulo' => ucfirst(nombre_mes($mes)).' '.$anio,                     // Ej. "Noviembre 2026".
            'inicio' => $inicio,                                                 // Fecha inicial.
            'fin' => $fin,                                                       // Fecha final.
            'organismo' => config('hydropay.organismo'),                         // Nombre del organismo.
            'pagos' => $pagos,                                                   // Detalle de pagos del mes.
            'ingresos' => [
                'cantidad' => $pagos->count(),                                   // Número de pagos.
                'total' => round((float) $pagos->sum('monto'), 2),               // Dinero recibido.
            ],
            'por_metodo' => $porMetodo,                                          // Ingresos por método.
            'recibos' => [
                'cantidad' => $recibos->count(),                                 // Recibos emitidos.
                'total' => round((float) $recibos->sum('monto'), 2),             // Importe emitido.
                'por_estado' => $porEstado,                                      // Detalle por estado.
            ],
            'cartera' => [
                'cantidad' => $cartera->count(),                                 // Recibos que se deben hoy.
                'total' => round((float) $cartera->sum('monto'), 2),             // Importe que se debe hoy.
                'vencidos' => $cartera->where('estado', Cobro::VENCIDO)->count(),// De ellos, cuántos vencidos.
            ],
            'deudores' => $deudores,                                             // Principales deudores.
            'clientes' => [
                'total' => Cliente::count(),                                     // Clientes registrados.
                'activos' => Cliente::where('activo', true)->count(),            // Clientes activos.
                'nuevos' => Cliente::whereBetween('created_at', [$inicio, $fin])->count(), // Altas del mes.
            ],
        ];
    }

    /**
     * Filas del archivo CSV: un renglón por cada pago del mes.
     *
     * @param  array<string, mixed>  $reporte  Resultado de generar().
     * @return array<int, array<int, string>>
     */
    public function filasCsv(array $reporte): array
    {
        // Encabezados de las columnas.
        $filas = [['Fecha de pago', 'Folio de pago', 'Cuenta', 'Cliente', 'Periodo', 'Método', 'Referencia', 'Importe', 'Registró']];

        // Un renglón por pago, con los datos ya formateados para Excel.
        foreach ($reporte['pagos'] as $pago) {
            $filas[] = [
                $pago->fecha_pago->format('Y-m-d'),                  // Fecha ISO (Excel la reconoce).
                (string) $pago->id,                                  // Folio interno.
                $pago->cobro->cliente->numero_cuenta,                // Cuenta del cliente.
                $pago->cobro->cliente->nombre,                       // Nombre del cliente.
                $pago->cobro->periodo,                               // Periodo pagado.
                ucfirst($pago->metodo),                              // Método.
                (string) ($pago->referencia ?? ''),                  // Referencia o vacío.
                number_format((float) $pago->monto, 2, '.', ''),     // Importe sin separador de miles.
                $pago->registradoPor?->name ?? 'Usuario eliminado',  // Quién lo registró.
            ];
        }

        // Renglón final con el total del mes.
        $filas[] = ['', '', '', '', '', '', 'TOTAL', number_format((float) $reporte['ingresos']['total'], 2, '.', ''), ''];

        // Arreglo listo para fputcsv.
        return $filas;
    }
}
