<?php

// Espacio de nombres de las políticas de autorización.
namespace App\Policies;

// Modelo del recibo.
use App\Models\Cobro;
// Modelo del usuario autenticado.
use App\Models\User;
// Respuesta de autorización con mensaje.
use Illuminate\Auth\Access\Response;
// Clase base de modelos (la firma debe coincidir con PoliticaBase).
use Illuminate\Database\Eloquent\Model;

// Política del modelo Cobro (recibos). Agrega reglas propias a las de PoliticaBase.
class CobroPolicy extends PoliticaBase
{
    // Modificar un recibo: además del rol, revisa que no esté pagado ni cancelado.
    public function update(User $user, Model $cobro): Response
    {
        // Primero se aplica la regla general por rol (admin o cajero).
        $permiso = parent::update($user, $cobro);

        // Si el rol no puede operar, se devuelve ese rechazo.
        if ($permiso->denied()) {
            return $permiso;
        }

        // Un recibo liquidado queda cerrado para conservar la integridad financiera.
        if ($cobro instanceof Cobro && $cobro->tienePago()) {
            return Response::deny(__('hydropay.cobros.liquidado_no_editable'));
        }

        // Un recibo cancelado solo lo puede reactivar o modificar un administrador.
        if ($cobro instanceof Cobro && $cobro->estado === Cobro::CANCELADO && ! $user->esAdmin()) {
            return Response::deny(__('hydropay.cobros.cancelado_solo_admin'));
        }

        // En cualquier otro caso se permite la modificación.
        return Response::allow();
    }

    // Cancelar un recibo vigente es una decisión administrativa, no una acción de caja.
    public function cancelar(User $user, Cobro $cobro): Response
    {
        // Solo el administrador puede cancelar.
        return $user->esAdmin()
            ? Response::allow()
            : Response::deny(__('hydropay.cobros.cancelar_solo_admin'));
    }
}
