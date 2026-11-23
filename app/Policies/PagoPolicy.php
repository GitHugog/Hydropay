<?php

// Espacio de nombres de las políticas de autorización.
namespace App\Policies;

// Modelo del usuario autenticado.
use App\Models\User;
// Respuesta de autorización con mensaje.
use Illuminate\Auth\Access\Response;
// Clase base de modelos (la firma debe coincidir con PoliticaBase).
use Illuminate\Database\Eloquent\Model;

// Política del módulo de pagos: los pagos se consultan y registran, pero no se editan ni eliminan
// para conservar el historial financiero.
class PagoPolicy extends PoliticaBase
{
    // Nadie modifica un pago ya registrado.
    public function update(User $user, Model $modelo): Response
    {
        return Response::deny(__('hydropay.pagos.inmutable'));
    }

    // Nadie elimina un pago ya registrado (ni siquiera el administrador).
    public function delete(User $user, Model $modelo): Response
    {
        return Response::deny(__('hydropay.pagos.inmutable'));
    }
}
