<?php

// Espacio de nombres de los controladores de la API REST.
namespace App\Http\Controllers\Api;

// Controlador base.
use App\Http\Controllers\Controller;
// Form Request compartido con la interfaz web.
use App\Http\Requests\CobroRequest;
// Resource del recibo.
use App\Http\Resources\CobroResource;
// Modelo del recibo.
use App\Models\Cobro;
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

// API DE RECIBOS (cobros). Mismas reglas que la interfaz web (CobroPolicy + CobroRequest).
class CobroController extends Controller
{
    // GET /api/v1/cobros?estado=vencido&busqueda=&cliente_id=&por_pagina=
    public function index(Request $request): AnonymousResourceCollection
    {
        // Permiso de consulta.
        Gate::authorize('viewAny', Cobro::class);

        // Estado solicitado; se ignora si no es válido.
        $estado = (string) $request->input('estado', '');
        $estado = in_array($estado, Cobro::ESTADOS, true) ? $estado : '';

        // Consulta paginada.
        $cobros = Cobro::query()
            ->with(['cliente', 'pago'])                                                  // Relaciones precargadas.
            ->filtrar((string) $request->input('busqueda', ''), $estado)                  // Búsqueda y estado.
            ->when($request->filled('cliente_id'), fn ($q) => $q->where('cliente_id', $request->integer('cliente_id'))) // Por cliente.
            ->latest('id')
            ->paginate(max(1, min($request->integer('por_pagina', config('hydropay.api_por_pagina')), config('hydropay.api_por_pagina_max'))))
            ->withQueryString();

        // Colección paginada.
        return CobroResource::collection($cobros);
    }

    // POST /api/v1/cobros → alta del recibo.
    public function store(CobroRequest $request): JsonResponse
    {
        // Inserta el recibo.
        $cobro = Cobro::create($request->validated());

        // Si ya nace vencido, se marca de una vez.
        $cobro->recalcularEstado();

        // Bitácora.
        Log::info('API: cobro registrado', ['cobro_id' => $cobro->id, 'user_id' => $request->user()->id]);

        // 201 con el recibo y su cliente.
        return (new CobroResource($cobro->load(['cliente', 'pago'])))->response()->setStatusCode(201);
    }

    // GET /api/v1/cobros/{cobro}
    public function show(Cobro $cobro): CobroResource
    {
        // Permiso de consulta.
        Gate::authorize('view', $cobro);

        // Recibo con cliente y pago (y quién lo registró).
        return new CobroResource($cobro->load(['cliente', 'pago.registradoPor']));
    }

    // PUT/PATCH /api/v1/cobros/{cobro} (requiere el cuerpo completo: cliente_id, periodo, monto, estado...)
    public function update(CobroRequest $request, Cobro $cobro): CobroResource
    {
        // Guarda cambios.
        $cobro->update($request->validated());

        // Recalcula el estado.
        $cobro->recalcularEstado();

        // Bitácora.
        Log::info('API: cobro actualizado', ['cobro_id' => $cobro->id, 'estado' => $cobro->estado, 'user_id' => $request->user()->id]);

        // Recibo actualizado.
        return new CobroResource($cobro->load(['cliente', 'pago']));
    }

    // DELETE /api/v1/cobros/{cobro} (solo administrador y solo sin pago)
    public function destroy(Request $request, Cobro $cobro): JsonResponse
    {
        // Permiso de eliminación.
        Gate::authorize('delete', $cobro);

        // 409 Conflict: un recibo pagado forma parte del historial financiero.
        if ($cobro->pago()->exists()) {
            return response()->json(['message' => __('hydropay.cobros.no_eliminar_con_pago')], 409);
        }

        // Elimina.
        $cobro->delete();

        // Bitácora.
        Log::warning('API: cobro eliminado', ['cobro_id' => $cobro->id, 'user_id' => $request->user()->id]);

        // Confirmación.
        return response()->json(['message' => __('hydropay.cobros.eliminado')]);
    }
}
