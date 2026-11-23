<?php

// Espacio de nombres de los servicios (lógica de negocio reutilizable).
namespace App\Services;

// Modelos que se resumen en el panel.
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Pago;
// Fachada de caché de Laravel.
use Illuminate\Support\Facades\Cache;

// SERVICIO DEL PANEL PRINCIPAL (lo usan el Dashboard web y el endpoint GET /api/v1/resumen).
//   - CACHE: el resultado se guarda 5 minutos para no consultar la base en cada visita.
//   - COLLECTIONS: los recibos se agrupan y suman con groupBy(), map() y sum().
// Los modelos Cliente, Cobro y Pago llaman a olvidar() cuando cambian sus datos.
class ResumenService
{
    // Nombre de la llave donde se guarda el resumen en caché.
    public const LLAVE = 'hydropay.dashboard.resumen';

    /**
     * Devuelve las cifras del panel (desde caché si todavía están vigentes).
     *
     * @return array<string, mixed>
     */
    public static function obtener(): array
    {
        // remember(): si la llave existe la devuelve; si no, ejecuta la función, guarda el resultado y lo devuelve.
        return Cache::remember(self::LLAVE, now()->addMinutes(5), function () {
            // Se traen solo las columnas necesarias de todos los recibos como una Collection.
            $cobros = Cobro::query()->get(['estado', 'monto']);

            // Collection: agrupa por estado y calcula cantidad e importe de cada grupo.
            $porEstado = $cobros->groupBy('estado')->map(fn ($grupo) => [
                'cantidad' => $grupo->count(),
                'monto' => round((float) $grupo->sum('monto'), 2),
            ]);

            // Total cobrado: suma de todos los pagos registrados.
            $cobrado = round((float) Pago::query()->sum('monto'), 2);

            // Importe por cobrar: recibos pendientes o vencidos que todavía no tienen pago.
            $porCobrar = round((float) Cobro::query()->porPagar()->sum('monto'), 2);

            // Arreglo final que consumen la vista y la API.
            return [
                'clientes' => Cliente::count(),                                       // Clientes registrados.
                'clientes_activos' => Cliente::where('activo', true)->count(),        // Clientes activos.
                'pagos' => Pago::count(),                                             // Pagos registrados.
                'pendientes' => $porEstado->get(Cobro::PENDIENTE)['cantidad'] ?? 0,   // Recibos sin pagar.
                'vencidos' => $porEstado->get(Cobro::VENCIDO)['cantidad'] ?? 0,       // Recibos vencidos.
                'por_estado' => $porEstado->toArray(),                                // Detalle por estado.
                'cobrado' => $cobrado,                                                // Dinero recibido.
                'por_cobrar' => $porCobrar,                                           // Dinero pendiente.
                'generado_en' => now()->toIso8601String(),                            // Momento del cálculo.
            ];
        });
    }

    // Borra el resumen guardado para que se recalcule en la siguiente visita.
    public static function olvidar(): void
    {
        Cache::forget(self::LLAVE);
    }
}
