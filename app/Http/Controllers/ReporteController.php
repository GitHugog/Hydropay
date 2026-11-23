<?php

// Espacio de nombres de los controladores web.
namespace App\Http\Controllers;

// Servicio que calcula el reporte.
use App\Services\ReporteMensualService;
// Respuesta de redirección.
use Illuminate\Http\RedirectResponse;
// Solicitud HTTP.
use Illuminate\Http\Request;
// Registro de eventos.
use Illuminate\Support\Facades\Log;
// Tipo de respuesta de vista.
use Illuminate\View\View;
// Respuesta de descarga en flujo (streaming).
use Symfony\Component\HttpFoundation\StreamedResponse;

// CONTROLADOR DE REPORTES MENSUALES (todos los roles pueden consultarlos).
// Rutas con parámetros OPCIONALES: /reportes, /reportes/2026, /reportes/2026/11.
class ReporteController extends Controller
{
    // Laravel inyecta el servicio automáticamente (contenedor de dependencias).
    public function __construct(private readonly ReporteMensualService $reportes)
    {
    }

    // GET /reportes/{anio?}/{mes?} → reporte del mes indicado o del mes actual.
    public function index(Request $request, ?int $anio = null, ?int $mes = null): View|RedirectResponse
    {
        // Si el usuario eligió un mes en el selector (?periodo=2026-11), se redirige a la URL limpia /reportes/2026/11.
        if ($request->filled('periodo')) {
            // El selector type="month" envía el formato AAAA-MM.
            $datos = $request->validate(['periodo' => ['date_format:Y-m']]);

            // Separa año y mes.
            [$anioElegido, $mesElegido] = array_map('intval', explode('-', $datos['periodo']));

            // Redirección a la ruta nombrada con parámetros.
            return redirect()->route('reportes.index', ['anio' => $anioElegido, 'mes' => $mesElegido]);
        }

        // Sin parámetros se usa el año y mes actuales; con solo el año se usa enero.
        $anio ??= (int) now()->year;
        $mes ??= $anio === (int) now()->year ? (int) now()->month : 1;

        // 404 si el mes o el año están fuera de rango.
        $this->validarPeriodo($anio, $mes);

        // Calcula el reporte y lo envía a la vista.
        return view('reportes.index', ['reporte' => $this->reportes->generar($anio, $mes)]);
    }

    // GET /reportes/{anio}/{mes}/csv → descarga el detalle de pagos del mes en formato CSV (Excel).
    public function exportar(Request $request, int $anio, int $mes): StreamedResponse
    {
        // 404 si el periodo no es válido.
        $this->validarPeriodo($anio, $mes);

        // Datos del reporte.
        $reporte = $this->reportes->generar($anio, $mes);

        // Filas del archivo (encabezado + un renglón por pago + total).
        $filas = $this->reportes->filasCsv($reporte);

        // Nombre del archivo: hydropay-reporte-2026-11.csv
        $archivo = sprintf('hydropay-reporte-%04d-%02d.csv', $anio, $mes);

        // Bitácora de exportación de información financiera.
        Log::info('Reporte mensual exportado', ['anio' => $anio, 'mes' => $mes, 'user_id' => $request->user()->id]);

        // streamDownload escribe el archivo directamente en la respuesta sin guardarlo en disco.
        return response()->streamDownload(function () use ($filas) {
            // Flujo de salida de PHP.
            $salida = fopen('php://output', 'w');

            // BOM UTF-8 para que Excel muestre bien los acentos.
            fwrite($salida, "\xEF\xBB\xBF");

            // Escribe cada fila separada por comas.
            foreach ($filas as $fila) {
                fputcsv($salida, $fila);
            }

            // Cierra el flujo.
            fclose($salida);
        }, $archivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // Comprueba que el mes esté entre 1 y 12 y el año en un rango razonable.
    private function validarPeriodo(int $anio, int $mes): void
    {
        abort_unless($mes >= 1 && $mes <= 12 && $anio >= 2000 && $anio <= 2100, 404);
    }
}
