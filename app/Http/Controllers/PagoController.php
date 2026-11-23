<?php

// Espacio de nombres de los controladores web.
namespace App\Http\Controllers;

// Form Request de pagos.
use App\Http\Requests\PagoRequest;
// Modelos involucrados.
use App\Models\Cobro;
use App\Models\Pago;
// Servicio que aplica las reglas de cobranza.
use App\Services\RegistroPagoService;
// Respuesta de redirección.
use Illuminate\Http\RedirectResponse;
// Solicitud HTTP.
use Illuminate\Http\Request;
// Fachada de autorización.
use Illuminate\Support\Facades\Gate;
// Registro de eventos.
use Illuminate\Support\Facades\Log;
// Almacenamiento de archivos.
use Illuminate\Support\Facades\Storage;
// Excepción de validación.
use Illuminate\Validation\ValidationException;
// Tipo de respuesta de vista.
use Illuminate\View\View;
// Respuesta de descarga de archivo.
use Symfony\Component\HttpFoundation\StreamedResponse;
// Cualquier error de PHP.
use Throwable;

// CONTROLADOR DE PAGOS: historial, registro de un pago completo por recibo y descarga de comprobantes.
// Los pagos no se editan ni eliminan (PagoPolicy) para conservar el historial financiero.
class PagoController extends Controller
{
    // GET /pagos → historial con búsqueda, filtro por método y paginación.
    public function index(Request $request): View
    {
        // Todos los roles pueden consultar.
        Gate::authorize('viewAny', Pago::class);

        // Filtros enviados por GET.
        $busqueda = (string) $request->input('busqueda', '');
        $metodo = (string) $request->input('metodo', '');

        // Un método inexistente se ignora.
        if (! in_array($metodo, Pago::METODOS, true)) {
            $metodo = '';
        }

        // Consulta paginada con relaciones precargadas.
        $pagos = Pago::query()
            ->with(['cobro.cliente', 'registradoPor'])
            ->filtrar($busqueda, $metodo)
            ->latest('id')
            ->paginate(config('hydropay.web_por_pagina'))
            ->withQueryString();

        // Vista del historial.
        return view('pagos.index', [
            'pagos' => $pagos,
            'busqueda' => $busqueda,
            'metodo' => $metodo,
            'metodos' => Pago::METODOS,
        ]);
    }

    // GET /pagos/create?cobro_id=8 → formulario; solo lista recibos que todavía se pueden cobrar.
    public function create(Request $request): View
    {
        // Solo admin y cajero.
        Gate::authorize('create', Pago::class);

        // Recibos pendientes o vencidos sin pago, ordenados por fecha límite.
        $cobros = Cobro::query()
            ->with('cliente')
            ->porPagar()
            ->orderBy('fecha_vencimiento')
            ->get();

        // Vista del formulario.
        return view('pagos.create', [
            'cobros' => $cobros,
            'cobroId' => $request->query('cobro_id'),
            'metodos' => Pago::METODOS,
        ]);
    }

    // POST /pagos → registra el pago completo mediante RegistroPagoService.
    public function store(PagoRequest $request, RegistroPagoService $servicio): RedirectResponse
    {
        try {
            // El servicio valida reglas de negocio, guarda el comprobante y dispara el evento.
            $pago = $servicio->registrar($request->validated(), $request->user(), $request->file('comprobante'));
        } catch (ValidationException $e) {
            // Las reglas de negocio (recibo pagado, abono, duplicado) regresan al formulario con errores.
            throw $e;
        } catch (Throwable $e) {
            // Error inesperado: se registra el detalle técnico y se muestra un mensaje amable.
            Log::error('Error al registrar pago', ['error' => $e->getMessage(), 'user_id' => $request->user()->id]);

            // Regresa al formulario conservando lo capturado.
            return back()->withInput()->with('error', __('hydropay.pagos.error'));
        }

        // Muestra el comprobante del pago con un mensaje de éxito.
        return redirect()
            ->route('pagos.show', $pago)
            ->with('mensaje', __('hydropay.pagos.creado'));
    }

    // GET /pagos/{pago} → detalle del pago.
    public function show(Pago $pago): View
    {
        // Todos los roles pueden consultar.
        Gate::authorize('view', $pago);

        // Carga recibo, cliente y usuario que registró.
        $pago->load(['cobro.cliente', 'registradoPor']);

        // Vista del detalle.
        return view('pagos.show', compact('pago'));
    }

    // GET /pagos/{pago}/comprobante → descarga protegida del archivo (no está en una carpeta pública).
    public function comprobante(Pago $pago): StreamedResponse
    {
        // Debe tener permiso de consulta.
        Gate::authorize('view', $pago);

        // 404 si el pago no tiene comprobante o el archivo ya no existe en el disco.
        abort_unless($pago->comprobante && Storage::disk('local')->exists($pago->comprobante), 404);

        // Bitácora de acceso a un documento financiero.
        Log::info('Descarga de comprobante', ['pago_id' => $pago->id, 'user_id' => auth()->id()]);

        // Envía el archivo al navegador como descarga.
        return Storage::disk('local')->download($pago->comprobante);
    }
}
