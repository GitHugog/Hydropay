<?php

// Espacio de nombres de los controladores web.
namespace App\Http\Controllers;

// Form Request de zonas.
use App\Http\Requests\ZonaRequest;
// Modelo de la zona.
use App\Models\Zona;
// Respuesta de redirección.
use Illuminate\Http\RedirectResponse;
// Solicitud HTTP.
use Illuminate\Http\Request;
// Registro de eventos.
use Illuminate\Support\Facades\Log;
// Tipo de respuesta de vista.
use Illuminate\View\View;

// CONTROLADOR DE ZONAS (municipio y localidad), solo administrador.
// Sus rutas tienen el middleware "rol:admin" y ZonaRequest revisa el Gate "administrar-catalogos".
class ZonaController extends Controller
{
    // GET /zonas → listado con búsqueda y número de clientes por zona.
    public function index(Request $request): View
    {
        // Texto de búsqueda (municipio o localidad).
        $busqueda = (string) $request->input('busqueda', '');

        // Consulta paginada.
        $zonas = Zona::query()
            ->withCount('clientes')                         // Agrega clientes_count a cada zona.
            ->when($busqueda, fn ($q, $b) => $q->where(function ($sub) use ($b) {
                $sub->where('municipio', 'like', "%{$b}%")->orWhere('localidad', 'like', "%{$b}%");
            }))
            ->ordenadas()                                   // Por municipio y localidad.
            ->paginate(config('hydropay.web_por_pagina'))
            ->withQueryString();

        return view('zonas.index', compact('zonas', 'busqueda'));
    }

    // GET /zonas/create → formulario de alta.
    public function create(): View
    {
        return view('zonas.create');
    }

    // POST /zonas → guarda una zona nueva.
    public function store(ZonaRequest $request): RedirectResponse
    {
        // Inserta la zona con los datos validados.
        $zona = Zona::create($request->validated());

        // Bitácora.
        Log::info('Zona registrada', ['zona_id' => $zona->id, 'user_id' => $request->user()->id]);

        return redirect()->route('zonas.index')->with('mensaje', __('hydropay.zonas.creada'));
    }

    // GET /zonas/{zona}/edit → formulario de edición.
    public function edit(Zona $zona): View
    {
        return view('zonas.edit', compact('zona'));
    }

    // PUT /zonas/{zona} → actualiza la zona.
    public function update(ZonaRequest $request, Zona $zona): RedirectResponse
    {
        // Guarda los cambios.
        $zona->update($request->validated());

        // Bitácora.
        Log::info('Zona actualizada', ['zona_id' => $zona->id, 'user_id' => $request->user()->id]);

        return redirect()->route('zonas.index')->with('mensaje', __('hydropay.zonas.actualizada'));
    }

    // DELETE /zonas/{zona} → elimina la zona solo si no tiene clientes asignados.
    public function destroy(Request $request, Zona $zona): RedirectResponse
    {
        // Integridad: no se deja a ningún cliente apuntando a una zona inexistente.
        if ($zona->clientes()->exists()) {
            return redirect()->route('zonas.index')->with('error', __('hydropay.zonas.con_clientes'));
        }

        // Elimina el registro.
        $zona->delete();

        // Bitácora.
        Log::warning('Zona eliminada', ['zona_id' => $zona->id, 'user_id' => $request->user()->id]);

        return redirect()->route('zonas.index')->with('mensaje', __('hydropay.zonas.eliminada'));
    }
}
