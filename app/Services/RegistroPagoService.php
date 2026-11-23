<?php

// Espacio de nombres de los servicios.
namespace App\Services;

// Evento que se dispara después de registrar un pago.
use App\Events\PagoRegistrado;
// Modelos involucrados.
use App\Models\Cobro;
use App\Models\Pago;
use App\Models\User;
// Excepción de base de datos (por ejemplo, violación del índice único).
use Illuminate\Database\QueryException;
// Archivo subido por el usuario.
use Illuminate\Http\UploadedFile;
// Transacciones de base de datos.
use Illuminate\Support\Facades\DB;
// Registro de eventos (Logging).
use Illuminate\Support\Facades\Log;
// Almacenamiento de archivos (File Storage).
use Illuminate\Support\Facades\Storage;
// Excepción de validación (Laravel la convierte en errores del formulario o en 422 JSON).
use Illuminate\Validation\ValidationException;
// Cualquier error de PHP.
use Throwable;

// SERVICIO DE COBRANZA: registra el pago completo de un recibo.
// Lo comparten PagoController (web) y Api\PagoController para aplicar exactamente las mismas reglas:
//   1. Un recibo admite un solo pago.
//   2. No se aceptan abonos: el importe se toma del recibo, nunca del formulario.
//   3. Un recibo cancelado no se cobra.
//   4. Si algo falla, no queda ni el pago ni el archivo del comprobante.
class RegistroPagoService
{
    /**
     * Registra el pago y devuelve el modelo guardado.
     *
     * @param  array<string, mixed>  $datos  Datos validados por PagoRequest.
     *
     * @throws ValidationException Cuando el recibo no admite pago o el monto enviado no coincide.
     */
    public function registrar(array $datos, User $usuario, ?UploadedFile $comprobante = null): Pago
    {
        // Ruta del comprobante guardado; se conserva para borrarlo si la operación falla.
        $ruta = null;

        try {
            // La transacción garantiza que se guarde todo o nada.
            $pago = DB::transaction(function () use ($datos, $usuario, $comprobante, &$ruta) {
                // Bloquea el recibo mientras se revisa, para que dos cajeros no lo cobren al mismo tiempo.
                $cobro = Cobro::query()->lockForUpdate()->findOrFail($datos['cobro_id']);

                // Actualiza el estado por si el recibo venció desde la última consulta.
                $cobro->recalcularEstado();

                // Un recibo cancelado o ya pagado no admite otro pago.
                if ($cobro->estado === Cobro::CANCELADO || $cobro->pago()->exists()) {
                    throw ValidationException::withMessages([
                        'cobro_id' => __('hydropay.pagos.no_admite_pago'),
                    ]);
                }

                // Si se envió un monto (formulario alterado o API), debe ser exactamente el del recibo.
                if (array_key_exists('monto', $datos) && $datos['monto'] !== null) {
                    // Se compara en centavos (enteros) para evitar errores de redondeo con decimales.
                    $enviado = (int) round(((float) $datos['monto']) * 100);
                    $esperado = (int) round(((float) $cobro->monto) * 100);

                    // Cualquier diferencia (abono o importe mayor) se rechaza.
                    if ($enviado !== $esperado) {
                        throw ValidationException::withMessages([
                            'monto' => __('hydropay.pagos.sin_abonos', ['monto' => formato_moneda($cobro->monto)]),
                        ]);
                    }
                }

                // El comprobante se guarda en el disco privado "local" (storage/app/private/comprobantes).
                if ($comprobante) {
                    $ruta = $comprobante->store('comprobantes', 'local');
                }

                // Se crea el pago con el importe tomado del recibo (no del formulario).
                return Pago::create([
                    'cobro_id' => $cobro->id,                          // Recibo liquidado.
                    'user_id' => $usuario->id,                         // Quién registró el pago.
                    'monto' => $cobro->monto,                          // Importe completo del recibo.
                    'metodo' => $datos['metodo'],                      // Método elegido.
                    'referencia' => $datos['referencia'] ?? null,      // Referencia opcional.
                    'fecha_pago' => $datos['fecha_pago'],              // Fecha de recepción.
                    'comprobante' => $ruta,                            // Ruta privada o null.
                ]);
            });
        } catch (QueryException $e) {
            // El índice UNIQUE de pagos.cobro_id también bloquea dos pagos simultáneos del mismo recibo.
            $this->borrarComprobante($ruta);

            // Se registra el conflicto para auditoría.
            Log::warning('Intento de pago duplicado o conflicto de integridad', [
                'cobro_id' => $datos['cobro_id'] ?? null,
                'user_id' => $usuario->id,
                'error' => $e->getMessage(),
            ]);

            // Se informa al usuario como error de validación del campo cobro_id.
            throw ValidationException::withMessages([
                'cobro_id' => __('hydropay.pagos.duplicado'),
            ]);
        } catch (Throwable $e) {
            // Cualquier otro error (incluida la validación) también elimina el archivo ya guardado.
            $this->borrarComprobante($ruta);

            // Se vuelve a lanzar para que el controlador decida cómo responder.
            throw $e;
        }

        // EVENTO: actualiza el estado del recibo y notifica al cliente (ver app/Listeners).
        event(new PagoRegistrado($pago->fresh(['cobro.cliente'])));

        // LOG: deja constancia del pago para auditoría.
        Log::info('Pago completo registrado', [
            'pago_id' => $pago->id,
            'cobro_id' => $pago->cobro_id,
            'monto' => $pago->monto,
            'user_id' => $usuario->id,
        ]);

        // Pago listo para mostrarse o devolverse en JSON.
        return $pago;
    }

    // Elimina el comprobante del disco privado si se alcanzó a guardar.
    private function borrarComprobante(?string $ruta): void
    {
        // Solo si existe una ruta.
        if ($ruta) {
            Storage::disk('local')->delete($ruta);
        }
    }
}
