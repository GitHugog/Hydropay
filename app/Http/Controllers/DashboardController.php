<?php

// Espacio de nombres de los controladores web.
namespace App\Http\Controllers;

// Modelos consultados.
use App\Models\Cobro;
use App\Models\Pago;
// Servicio con Cache y Collections.
use App\Services\ResumenService;
// Tipo de respuesta de vista.
use Illuminate\View\View;

// PANEL PRINCIPAL: indicadores generales, últimos pagos y recibos vencidos.
class DashboardController extends Controller
{
    // GET /dashboard
    public function index(): View
    {
        // Cifras calculadas con Collections y guardadas en Cache (ver ResumenService).
        $resumen = ResumenService::obtener();

        // Últimos cinco pagos con su recibo y cliente (carga anticipada para evitar consultas repetidas).
        $ultimosPagos = Pago::with('cobro.cliente')
            ->latest('id')
            ->take(5)
            ->get();

        // Cinco recibos vencidos más antiguos, para dar seguimiento.
        $vencidos = Cobro::with('cliente')
            ->where('estado', Cobro::VENCIDO)
            ->orderBy('fecha_vencimiento')
            ->take(5)
            ->get();

        // Vista resources/views/dashboard.blade.php.
        return view('dashboard', compact('resumen', 'ultimosPagos', 'vencidos'));
    }
}
