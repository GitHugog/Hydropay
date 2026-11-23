<?php

// Espacio de nombres de las políticas de autorización.
namespace App\Policies;

// Modelo del usuario autenticado.
use App\Models\User;
// Respuesta de autorización que permite incluir un mensaje de rechazo.
use Illuminate\Auth\Access\Response;
// Clase base de los modelos (para aceptar Cliente, Cobro o Pago).
use Illuminate\Database\Eloquent\Model;

// Reglas de permiso compartidas por los módulos de clientes, cobros y pagos:
//   - Cualquier usuario con sesión puede consultar.
//   - Administrador y cajero pueden registrar y modificar.
//   - Solo el administrador puede eliminar.
// Laravel llama a estos métodos con Gate::authorize(), @can en Blade y $user->can().
abstract class PoliticaBase
{
    // Permiso para ver listados.
    public function viewAny(User $user): bool
    {
        // Todos los roles pueden consultar.
        return true;
    }

    // Permiso para ver el detalle de un registro.
    public function view(User $user, Model $modelo): bool
    {
        // Todos los roles pueden consultar.
        return true;
    }

    // Permiso para registrar información nueva.
    public function create(User $user): Response
    {
        // Admin y cajero pasan; consulta recibe un rechazo con mensaje (HTTP 403).
        return $user->puedeOperar()
            ? Response::allow()
            : Response::deny(__('hydropay.permisos.solo_consulta'));
    }

    // Permiso para modificar información existente.
    public function update(User $user, Model $modelo): Response
    {
        // Misma regla que para registrar.
        return $user->puedeOperar()
            ? Response::allow()
            : Response::deny(__('hydropay.permisos.solo_consulta'));
    }

    // Permiso para eliminar: acción reservada al administrador.
    public function delete(User $user, Model $modelo): Response
    {
        // Solo el rol admin pasa.
        return $user->esAdmin()
            ? Response::allow()
            : Response::deny(__('hydropay.permisos.solo_admin_elimina'));
    }
}
