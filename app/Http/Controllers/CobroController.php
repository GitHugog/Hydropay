<?php

// Espacio de nombres de los controladores web.
namespace App\Http\Controllers;

// Form Request de recibos.
use App\Http\Requests\CobroRequest;
// Modelos involucrados.
use App\Models\Cliente;
use App\Models\Cobro;
// Servicio que genera los recibos del periodo según la cuota de cada cliente.
use App\Services\GeneradorRecibosService;
// Colección de Eloquent (tipo de retorno).
use Illuminate\Database\Eloquent\Collection;
// Respuesta de redirección.
use Illuminate\Http\RedirectResponse;
// Solicitud HTTP.
use Illuminate\Http\Request;
// Fachada de autorización.
use Illuminate\Support\Facades\Gate;
// Registro de eventos.
use Illuminate\Support\Facades\Log;
// Tipo de respuesta de vista.
use Illuminate\View\View;
// Cualquier error de PHP.
use Throwable;

// CONTROLADOR DE RECIBOS (cobros), módulo central de HydroPay.
// Permisos: CobroPolicy. Validación: CobroRequest. Rutas: cobros.*
class CobroController extends Controller
{
    // GET /cobros → listado con búsqueda, filtro por estado y paginación.
    public function index(Request $request): View
    {
        // Todos los roles pueden consultar.
        Gate::authorize('viewAny', Cobro::class);

        // Texto de búsqueda y estado elegido en el filtro.
        $busqueda = (string) $request->input('busqueda', '');
        $estado = (string) $request->input('estado', '');

        // Si el estado no es válido se ignora (evita filtros manipulados en la URL).
        if (! in_array($estado, Cobro::ESTADOS, true)) {
            $estado = '';
        }

        // Consulta paginada.
        $cobros = Cobro::query()
            ->with(['cliente', 'pago'])                   // Carga cliente y pago en dos consultas (no una por fila).
            ->filtrar($busqueda, $estado)                 // Scope del modelo.
            ->latest('id')                                // Más recientes primero.
            ->paginate(config('hydropay.web_por_pagina'))
            ->withQueryString();                          // Conserva filtros en la paginación.

        // Envía datos a la vista.
        return view('cobros.index', [
            'cobros' => $cobros,
            'busqueda' => $busqueda,
            'estado' => $estado,
            'estados' => Cobro::ESTADOS,
        ]);
    }

    // GET /cobros/create?cliente_id=5 → formulario de alta (el parámetro opcional preselecciona al cliente).
    public function create(Request $request): View
    {
        // Solo admin y cajero.
        Gate::authorize('create', Cobro::class);

        // Formulario con la lista de clientes activos.
        return view('cobros.create', [
            'clientes' => $this->clientesParaSelector(),
            'clienteId' => $request->query('cliente_id'),
        ]);
    }

    // POST /cobros → guarda el recibo.
    public function store(CobroRequest $request): RedirectResponse
    {
        // Inserta el recibo con los datos validados (el estado inicial es "pendiente").
        $cobro = Cobro::create($request->validated());

        // Si la fecha límite ya pasó, el recibo nace vencido.
        $cobro->recalcularEstado();

        // Bitácora.
        Log::info('Cobro registrado', ['cobro_id' => $cobro->id, 'user_id' => $request->user()->id]);

        // Redirige al detalle del recibo recién creado.
        return redirect()
            ->route('cobros.show', $cobro)
            ->with('mensaje', __('hydropay.cobros.creado'));
    }

    // GET /cobros/{cobro} → detalle del recibo y de su pago.
    public function show(Cobro $cobro): View
    {
        // Todos los roles pueden consultar.
        Gate::authorize('view', $cobro);

        // Carga el cliente, el pago y el usuario que lo registró.
        $cobro->load(['cliente', 'pago.registradoPor']);

        // Vista de detalle.
        return view('cobros.show', compact('cobro'));
    }

    // GET /cobros/{cobro}/edit → formulario de edición.
    public function edit(Cobro $cobro): View
    {
        // CobroPolicy niega (403) si el rol no opera, si el recibo ya está pagado o si está cancelado y no es admin.
        Gate::authorize('update', $cobro);

        // Formulario con el cliente actual incluido aunque esté inactivo.
        return view('cobros.edit', [
            'cobro' => $cobro,
            'clientes' => $this->clientesParaSelector($cobro->cliente_id),
        ]);
    }

    // PUT /cobros/{cobro} → actualiza el recibo (CobroRequest ya revisó permisos y datos).
    public function update(CobroRequest $request, Cobro $cobro): RedirectResponse
    {
        // Guarda los cambios.
        $cobro->update($request->validated());

        // Ajusta el estado: pendiente o vencido según la nueva fecha (cancelado se respeta).
        $cobro->recalcularEstado();

        // Bitácora.
        Log::info('Cobro actualizado', ['cobro_id' => $cobro->id, 'estado' => $cobro->estado, 'user_id' => $request->user()->id]);

        // Regresa al detalle.
        return redirect()
            ->route('cobros.show', $cobro)
            ->with('mensaje', __('hydropay.cobros.actualizado'));
    }

    // DELETE /cobros/{cobro} → elimina un recibo sin pago (solo administrador).
    public function destroy(Request $request, Cobro $cobro): RedirectResponse
    {
        // Permiso de eliminación (admin).
        Gate::authorize('delete', $cobro);

        // Integridad: un recibo con pago forma parte del historial financiero.
        if ($cobro->pago()->exists()) {
            return redirect()
                ->route('cobros.index')
                ->with('error', __('hydropay.cobros.no_eliminar_con_pago'));
        }

        // Manejo de errores en una operación crítica.
        try {
            // Elimina el recibo.
            $cobro->delete();
        } catch (Throwable $e) {
            // Se registra el detalle técnico y se muestra un mensaje amable.
            Log::error('Error al eliminar cobro', ['cobro_id' => $cobro->id, 'error' => $e->getMessage()]);

            return redirect()
                ->route('cobros.index')
                ->with('error', __('hydropay.cobros.error_eliminar'));
        }

        // Bitácora de la eliminación.
        Log::warning('Cobro eliminado', ['cobro_id' => $cobro->id, 'user_id' => $request->user()->id]);

        // Regresa al listado.
        return redirect()
            ->route('cobros.index')
            ->with('mensaje', __('hydropay.cobros.eliminado'));
    }

    // Clientes activos para el selector del formulario (más el cliente actual si estuviera inactivo).
    private function clientesParaSelector(?int $incluirId = null): Collection
    {
        return Cliente::query()
            ->where('activo', true)                                       // Solo activos.
            ->when($incluirId, fn ($q, $id) => $q->orWhere('id', $id))    // Más el cliente actual del recibo.
            ->orderBy('nombre')                                           // Orden alfabético.
            ->get(['id', 'nombre', 'numero_cuenta']);                     // Solo columnas necesarias.
    }

    // POST /cobros/generar → crea el recibo del periodo actual a todos los clientes con cuota (solo administrador).
    public function generar(Request $request, GeneradorRecibosService $generador): RedirectResponse
    {
        // La ruta ya tiene rol:admin; además se revisa el permiso de alta de recibos.
        Gate::authorize('create', Cobro::class);

        // Crea solo los recibos que falten (no repite periodos).
        $creados = $generador->generar();

        // Bitácora.
        Log::info('Generación manual de recibos', ['creados' => $creados, 'user_id' => $request->user()->id]);

        // Regresa al listado con el resultado.
        return redirect()
            ->route('cobros.index')
            ->with('mensaje', trans_choice('hydropay.cobros.generados', $creados, ['total' => $creados]));
    }
}
