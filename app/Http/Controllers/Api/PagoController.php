<?php

// Espacio de nombres de los controladores de la API REST.
namespace App\Http\Controllers\Api;

// Controlador base.
use App\Http\Controllers\Controller;
// Form Request compartido con la interfaz web.
use App\Http\Requests\PagoRequest;
// Resource del pago.
use App\Http\Resources\PagoResource;
// Modelo del pago.
use App\Models\Pago;
// Servicio con las reglas de cobranza.
use App\Services\RegistroPagoService;
// Respuesta JSON.
use Illuminate\Http\JsonResponse;
// Solicitud HTTP.
use Illuminate\Http\Request;
// Colección paginada de resources.
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
// Fachada de autorización.
use Illuminate\Support\Facades\Gate;
// Registro de eventos.
use Illuminate\Support\Facades\Log;
// Excepción de validación.
use Illuminate\Validation\ValidationException;
// Cualquier error de PHP.
use Throwable;

// API DE PAGOS: consulta y registro de pagos completos. No existe edición ni eliminación.
class PagoController extends Controller
{
    // GET /api/v1/pagos?metodo=efectivo&busqueda=&por_pagina=
    public function index(Request $request): AnonymousResourceCollection
    {
        // Permiso de consulta.
        Gate::authorize('viewAny', Pago::class);

        // Método solicitado; se ignora si no es válido.
        $metodo = (string) $request->input('metodo', '');
        $metodo = in_array($metodo, Pago::METODOS, true) ? $metodo : '';

        // Consulta paginada.
        $pagos = Pago::query()
            ->with(['cobro.cliente', 'registradoPor'])
            ->filtrar((string) $request->input('busqueda', ''), $metodo)
            ->latest('id')
            ->paginate(max(1, min($request->integer('por_pagina', config('hydropay.api_por_pagina')), config('hydropay.api_por_pagina_max'))))
            ->withQueryString();

        // Colección paginada.
        return PagoResource::collection($pagos);
    }

    // POST /api/v1/pagos → registra el pago completo de un recibo (JSON o multipart con comprobante).
    public function store(PagoRequest $request, RegistroPagoService $servicio): JsonResponse
    {
        try {
            // Mismo servicio que la interfaz web: importe tomado del recibo, sin abonos ni duplicados.
            $pago = $servicio->registrar($request->validated(), $request->user(), $request->file('comprobante'));
        } catch (ValidationException $e) {
            // Las reglas de negocio se devuelven como 422 con el detalle por campo.
            throw $e;
        } catch (Throwable $e) {
            // Error inesperado: se registra y se responde 500 sin exponer detalles técnicos.
            Log::error('API: error al registrar pago', ['error' => $e->getMessage(), 'user_id' => $request->user()->id]);

            return response()->json(['message' => __('hydropay.pagos.error')], 500);
        }

        // 201 con el pago y su recibo actualizado.
        return (new PagoResource($pago->load(['cobro.cliente', 'registradoPor'])))->response()->setStatusCode(201);
    }

    // GET /api/v1/pagos/{pago}
    public function show(Pago $pago): PagoResource
    {
        // Permiso de consulta.
        Gate::authorize('view', $pago);

        // Pago con recibo, cliente y usuario que lo registró.
        return new PagoResource($pago->load(['cobro.cliente', 'registradoPor']));
    }
}
