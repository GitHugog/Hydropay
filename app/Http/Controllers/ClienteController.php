<?php

// Espacio de nombres de los controladores web.
namespace App\Http\Controllers;

// Form Request que autoriza, limpia y valida los datos del cliente.
use App\Http\Requests\ClienteRequest;
// Modelo del cliente.
use App\Models\Cliente;
// Catálogo de zonas (municipio y localidad).
use App\Models\Zona;
// Servicio que crea el recibo del periodo según la cuota del cliente.
use App\Services\GeneradorRecibosService;
// Respuesta de redirección.
use Illuminate\Http\RedirectResponse;
// Solicitud HTTP (para leer filtros de búsqueda).
use Illuminate\Http\Request;
// Fachada de autorización (Policies).
use Illuminate\Support\Facades\Gate;
// Registro de eventos.
use Illuminate\Support\Facades\Log;
// Tipo de respuesta de vista.
use Illuminate\View\View;

// CONTROLADOR DE CLIENTES (CRUD completo).
// Permisos: ClientePolicy (consultar todos; registrar/editar admin y cajero; eliminar admin).
// Validación: ClienteRequest. Rutas: routes/web.php → clientes.*
class ClienteController extends Controller
{
    // GET /clientes → listado con búsqueda, filtro por estado y paginación.
    public function index(Request $request): View
    {
        // Comprueba el permiso de consulta (todos los roles lo tienen).
        Gate::authorize('viewAny', Cliente::class);

        // Texto de búsqueda enviado por GET (cadena vacía si no hay).
        $busqueda = (string) $request->input('busqueda', '');

        // Filtro de estado del registro: "activos", "inactivos" o vacío (todos).
        $estado = (string) $request->input('estado', '');

        // Filtro por zona: id de la zona o vacío (todas).
        $zona = (string) $request->input('zona', '');

        // Consulta paginada de clientes.
        $clientes = Cliente::query()
            ->with('zona')                                                    // Carga la zona de cada cliente (evita N+1).
            ->when($zona !== '', fn ($q) => $q->where('zona_id', (int) $zona))   // Solo clientes de esa zona.
            ->withCount('cobros')                                             // Agrega cobros_count a cada cliente.
            ->withSum(['cobros as adeudo_total' => fn ($q) => $q->porPagar()], 'monto') // Lo que debe (recibos sin pagar).
            ->buscar($busqueda)                                               // Scope del modelo: nombre, cuenta o dirección.
            ->when($estado === 'activos', fn ($q) => $q->where('activo', true))   // Solo activos.
            ->when($estado === 'inactivos', fn ($q) => $q->where('activo', false)) // Solo inactivos.
            ->orderBy('nombre')                                               // Orden alfabético.
            ->paginate(config('hydropay.web_por_pagina'))                     // 10 registros por página.
            ->withQueryString();                                              // Conserva los filtros al cambiar de página.

        // Envía los datos a la vista resources/views/clientes/index.blade.php.
        // Zonas para el selector del filtro.
        $zonas = Zona::ordenadas()->get();

        return view('clientes.index', compact('clientes', 'busqueda', 'estado', 'zona', 'zonas'));
    }

    // GET /clientes/create → formulario de alta.
    public function create(): View
    {
        // Solo admin y cajero pueden registrar.
        Gate::authorize('create', Cliente::class);

        // Muestra el formulario vacío con el catálogo de zonas.
        return view('clientes.create', ['zonas' => Zona::ordenadas()->get()]);
    }

    // POST /clientes → guarda el nuevo cliente (ClienteRequest ya autorizó, limpió y validó).
    public function store(ClienteRequest $request): RedirectResponse
    {
        // Datos validados y limpios.
        $datos = $request->validated();

        // El checkbox desmarcado no se envía; boolean() lo interpreta como false.
        $datos['activo'] = $request->boolean('activo');

        // Inserta el registro en la tabla clientes.
        $cliente = Cliente::create($datos);

        // Si el administrador le asignó cuota, se crea de inmediato el recibo del periodo actual.
        $recibo = $this->generarReciboActual($cliente);

        // Bitácora de la operación.
        Log::info('Cliente registrado', ['cliente_id' => $cliente->id, 'user_id' => $request->user()->id]);

        // Redirección al detalle del cliente (ahí se ve su cuota, sus recibos y cuánto debe).
        return redirect()
            ->route('clientes.show', $cliente)
            ->with('mensaje', $this->mensaje('hydropay.clientes.creado', $recibo));
    }

    // GET /clientes/{cliente} → detalle del cliente, sus recibos y su adeudo.
    public function show(Cliente $cliente): View
    {
        // Todos los roles pueden consultar.
        Gate::authorize('view', $cliente);

        // Carga los recibos del más reciente al más antiguo, con su pago (evita consultas repetidas).
        $cliente->load(['cobros' => fn ($q) => $q->with('pago')->latest('fecha_vencimiento')->latest('id')]);

        // Importe que el cliente debe actualmente.
        $adeudo = $cliente->adeudo();

        // Muestra la vista de detalle.
        return view('clientes.show', compact('cliente', 'adeudo'));
    }

    // GET /clientes/{cliente}/edit → formulario de edición.
    public function edit(Cliente $cliente): View
    {
        // Solo admin y cajero pueden editar.
        Gate::authorize('update', $cliente);

        // Formulario con los datos actuales y el catálogo de zonas.
        return view('clientes.edit', ['cliente' => $cliente, 'zonas' => Zona::ordenadas()->get()]);
    }

    // PUT /clientes/{cliente} → actualiza el cliente.
    public function update(ClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        // Datos validados y limpios.
        $datos = $request->validated();

        // Estado del checkbox (desmarcado = inactivo).
        $datos['activo'] = $request->boolean('activo');

        // Guarda los cambios.
        $cliente->update($datos);

        // Si cambió la cuota o se reactivó el cliente, se asegura el recibo del periodo actual.
        $recibo = $cliente->wasChanged(['tarifa', 'periodicidad', 'activo'])
            ? $this->generarReciboActual($cliente)
            : null;

        // Bitácora.
        Log::info('Cliente actualizado', ['cliente_id' => $cliente->id, 'user_id' => $request->user()->id]);

        // Regresa al detalle del cliente con mensaje de éxito.
        return redirect()
            ->route('clientes.show', $cliente)
            ->with('mensaje', $this->mensaje('hydropay.clientes.actualizado', $recibo));
    }

    // DELETE /clientes/{cliente} → elimina un cliente sin recibos (solo administrador).
    public function destroy(Request $request, Cliente $cliente): RedirectResponse
    {
        // Solo el administrador puede eliminar (además la ruta tiene el middleware rol:admin).
        Gate::authorize('delete', $cliente);

        // Integridad: no se borra un cliente con información financiera relacionada.
        if ($cliente->cobros()->exists()) {
            return redirect()
                ->route('clientes.index')
                ->with('error', __('hydropay.clientes.no_eliminar_con_cobros'));
        }

        // Elimina el registro.
        $cliente->delete();

        // Las eliminaciones se registran como advertencia para facilitar la auditoría.
        Log::warning('Cliente eliminado', ['cliente_id' => $cliente->id, 'user_id' => $request->user()->id]);

        // Regresa al listado con confirmación.
        return redirect()
            ->route('clientes.index')
            ->with('mensaje', __('hydropay.clientes.eliminado'));
    }

    // Crea el recibo del periodo actual si el cliente está activo y tiene cuota; devuelve el recibo o null.
    private function generarReciboActual(Cliente $cliente): ?\App\Models\Cobro
    {
        if (! $cliente->activo || ! $cliente->tieneCuota()) {
            return null;
        }

        return app(GeneradorRecibosService::class)->generarPara($cliente, now());
    }

    // Mensaje de éxito; si se generó un recibo, agrega su periodo e importe.
    private function mensaje(string $clave, ?\App\Models\Cobro $recibo): string
    {
        $texto = __($clave);

        if ($recibo) {
            $texto .= ' '.__('hydropay.clientes.recibo_generado', [
                'periodo' => $recibo->periodo,
                'monto' => formato_moneda((float) $recibo->monto),
            ]);
        }

        return $texto;
    }
}
