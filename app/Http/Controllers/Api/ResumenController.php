<?php

// Espacio de nombres de los controladores de la API REST.
namespace App\Http\Controllers\Api;

// Controlador base.
use App\Http\Controllers\Controller;
// Servicio del panel (Cache + Collections).
use App\Services\ResumenService;
// Respuesta JSON.
use Illuminate\Http\JsonResponse;

// GET /api/v1/resumen → indicadores del panel principal en formato JSON.
// Controlador de una sola acción (__invoke): la ruta lo llama directamente.
class ResumenController extends Controller
{
    // Devuelve las mismas cifras que ve el usuario en el Dashboard web.
    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => ResumenService::obtener()]);
    }
}
