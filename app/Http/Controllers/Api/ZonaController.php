<?php

// Espacio de nombres de los controladores de la API.
namespace App\Http\Controllers\Api;

// Controlador base.
use App\Http\Controllers\Controller;
// Modelo de la zona.
use App\Models\Zona;
// Respuesta JSON.
use Illuminate\Http\JsonResponse;

// API: GET /api/v1/zonas → catálogo de zonas con el número de clientes de cada una (habilidad "leer").
// Controlador de una sola acción (__invoke).
class ZonaController extends Controller
{
    public function __invoke(): JsonResponse
    {
        // Zonas ordenadas por municipio y localidad.
        $zonas = Zona::ordenadas()->withCount('clientes')->get()
            ->map(fn (Zona $zona) => [
                'id' => $zona->id,
                'municipio' => $zona->municipio,
                'localidad' => $zona->localidad,
                'clientes' => $zona->clientes_count,
            ]);

        return response()->json(['data' => $zonas]);
    }
}
