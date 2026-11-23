<?php

// Espacio de nombres de los controladores de la API REST.
namespace App\Http\Controllers\Api;

// Controlador base.
use App\Http\Controllers\Controller;
// Form Request compartido con la interfaz web.
use App\Http\Requests\ClienteRequest;
// Resources para dar forma al JSON.
use App\Http\Resources\ClienteResource;
use App\Http\Resources\CobroResource;
use App\Http\Resources\PagoResource;
// Modelos.
use App\Models\Cliente;
use App\Models\Cobro;
// Servicio que crea el recibo del periodo según la cuota del cliente.
use App\Services\GeneradorRecibosService;
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

// API DE CLIENTES (CRUD + estado de cuenta). Mismas Policies y validaciones que la interfaz web.
class ClienteController extends Controller
{
    // GET /api/v1/clientes?busqueda=&activo=1&por_pagina=15
    public function index(Request $request): AnonymousResourceCollection
    {
        // Permiso de consulta.
        Gate::authorize('viewAny', Cliente::class);

        // Consulta paginada con filtros opcionales.
        $clientes = Cliente::query()
            ->withCount('cobros')                                                        // Total de recibos.
            ->with('zona')                                                               // Zona de cada cliente.
            ->buscar((string) $request->input('busqueda', ''))                           // Texto libre.
            ->when($request->filled('zona_id'), fn ($q) => $q->where('zona_id', $request->integer('zona_id'))) // Filtro por zona.
            ->when($request->has('activo'), fn ($q) => $q->where('activo', $request->boolean('activo'))) // Filtro activo.
            ->orderBy('nombre')
            ->paginate($this->porPagina($request))
            ->withQueryString();

        // Colección con "data", "links" y "meta" de paginación.
        return ClienteResource::collection($clientes);
    }

    // POST /api/v1/clientes → alta (ClienteRequest autoriza, limpia y valida).
    public function store(ClienteRequest $request): JsonResponse
    {
        // Si no se envía "activo" el cliente nace activo.
        $datos = $request->validated() + ['activo' => true];

        // Inserta el registro.
        $cliente = Cliente::create($datos);

        // Con cuota asignada, se crea de inmediato el recibo del periodo actual.
        $this->generarReciboActual($cliente);

        // Bitácora.
        Log::info('API: cliente registrado', ['cliente_id' => $cliente->id, 'user_id' => $request->user()->id]);

        // 201 = creado.
        return (new ClienteResource($cliente->load('zona')))->response()->setStatusCode(201);
    }

    // GET /api/v1/clientes/{cliente}
    public function show(Cliente $cliente): ClienteResource
    {
        // Permiso de consulta.
        Gate::authorize('view', $cliente);

        // Agrega el conteo de recibos y devuelve el recurso.
        return new ClienteResource($cliente->load('zona')->loadCount('cobros'));
    }

    // PUT/PATCH /api/v1/clientes/{cliente}
    public function update(ClienteRequest $request, Cliente $cliente): ClienteResource
    {
        // Actualiza con los datos validados ("activo" solo cambia si se envía).
        $cliente->update($request->validated());

        // Si cambió la cuota o se reactivó, se asegura el recibo del periodo actual.
        if ($cliente->wasChanged(['tarifa', 'periodicidad', 'activo'])) {
            $this->generarReciboActual($cliente);
        }

        // Bitácora.
        Log::info('API: cliente actualizado', ['cliente_id' => $cliente->id, 'user_id' => $request->user()->id]);

        // Recurso actualizado.
        return new ClienteResource($cliente->load('zona')->loadCount('cobros'));
    }

    // DELETE /api/v1/clientes/{cliente} (token con habilidad "eliminar", solo administrador)
    public function destroy(Request $request, Cliente $cliente): JsonResponse
    {
        // Permiso de eliminación.
        Gate::authorize('delete', $cliente);

        // 409 Conflict: el cliente tiene recibos y no se puede borrar.
        if ($cliente->cobros()->exists()) {
            return response()->json(['message' => __('hydropay.clientes.no_eliminar_con_cobros')], 409);
        }

        // Elimina.
        $cliente->delete();

        // Bitácora.
        Log::warning('API: cliente eliminado', ['cliente_id' => $cliente->id, 'user_id' => $request->user()->id]);

        // Confirmación.
        return response()->json(['message' => __('hydropay.clientes.eliminado')]);
    }

    // GET /api/v1/clientes/{cliente}/estado-cuenta → recibos por pagar, total adeudado y último pago.
    public function estadoCuenta(Cliente $cliente): JsonResponse
    {
        // Permiso de consulta.
        Gate::authorize('view', $cliente);

        // Recibos pendientes o vencidos sin pago, del más antiguo al más reciente.
        $porPagar = $cliente->cobros()->porPagar()->with('pago')->orderBy('fecha_vencimiento')->get();

        // Último pago registrado del cliente (relación cliente → cobros → pagos).
        $ultimoPago = $cliente->pagos()->latest('pagos.fecha_pago')->latest('pagos.id')->first();

        // Respuesta con la estructura del estado de cuenta.
        return response()->json(['data' => [
            'cliente' => new ClienteResource($cliente),
            'recibos_por_pagar' => CobroResource::collection($porPagar),
            'total_adeudo' => round((float) $porPagar->sum('monto'), 2),
            'recibos_vencidos' => $porPagar->where('estado', Cobro::VENCIDO)->count(),
            'ultimo_pago' => $ultimoPago ? new PagoResource($ultimoPago) : null,
        ]]);
    }

    // Crea el recibo del periodo actual si el cliente está activo y tiene cuota.
    private function generarReciboActual(Cliente $cliente): void
    {
        if ($cliente->activo && $cliente->tieneCuota()) {
            app(GeneradorRecibosService::class)->generarPara($cliente, now());
        }
    }

    // Registros por página: valor enviado entre 1 y el máximo configurado.
    private function porPagina(Request $request): int
    {
        // Valor solicitado o el predeterminado de config/hydropay.php.
        $solicitado = $request->integer('por_pagina', config('hydropay.api_por_pagina'));

        // Se limita al rango permitido.
        return max(1, min($solicitado, config('hydropay.api_por_pagina_max')));
    }
}
