<?php

// Espacio de nombres de los controladores web.
namespace App\Http\Controllers;

// Form Request de usuarios.
use App\Http\Requests\UsuarioRequest;
// Modelo del usuario.
use App\Models\User;
// Respuesta de redirección.
use Illuminate\Http\RedirectResponse;
// Solicitud HTTP.
use Illuminate\Http\Request;
// Registro de eventos.
use Illuminate\Support\Facades\Log;
// Tipo de respuesta de vista.
use Illuminate\View\View;

// CONTROLADOR DE USUARIOS Y ROLES (solo administrador).
// Todas sus rutas tienen el middleware "rol:admin" (routes/web.php) y UsuarioRequest revisa el Gate "gestionar-usuarios".
// Es el "registro de usuarios" del sistema: el personal no se registra solo, lo da de alta el administrador.
class UsuarioController extends Controller
{
    // GET /usuarios → listado con búsqueda por nombre o correo.
    public function index(Request $request): View
    {
        // Texto de búsqueda.
        $busqueda = (string) $request->input('busqueda', '');

        // Consulta paginada.
        $usuarios = User::query()
            // Solo filtra si se escribió algo.
            ->when($busqueda, fn ($q, $b) => $q->where(function ($sub) use ($b) {
                // Coincidencia en nombre o correo.
                $sub->where('name', 'like', "%{$b}%")->orWhere('email', 'like', "%{$b}%");
            }))
            ->orderBy('name')                                 // Orden alfabético.
            ->paginate(config('hydropay.web_por_pagina'))
            ->withQueryString();

        // Vista del listado.
        return view('usuarios.index', compact('usuarios', 'busqueda'));
    }

    // GET /usuarios/create → formulario de alta.
    public function create(): View
    {
        return view('usuarios.create');
    }

    // POST /usuarios → crea un usuario con el rol elegido.
    public function store(UsuarioRequest $request): RedirectResponse
    {
        // El cast "hashed" del modelo cifra la contraseña al guardarla.
        $usuario = User::create($request->validated());

        // Bitácora administrativa.
        Log::info('Usuario creado por administrador', [
            'admin_id' => $request->user()->id,
            'user_id' => $usuario->id,
            'rol' => $usuario->rol,
        ]);

        // Regresa al listado con confirmación.
        return redirect()->route('usuarios.index')->with('mensaje', __('hydropay.usuarios.creado'));
    }

    // GET /usuarios/{usuario}/edit → formulario de edición.
    public function edit(User $usuario): View
    {
        return view('usuarios.edit', compact('usuario'));
    }

    // PUT /usuarios/{usuario} → actualiza nombre, correo, rol y (opcionalmente) la contraseña.
    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        // Datos validados.
        $datos = $request->validated();

        // Un administrador no puede cambiarse a sí mismo el rol (evita quedarse sin acceso).
        if ($usuario->is($request->user())) {
            $datos['rol'] = $usuario->rol;
        }

        // Siempre debe quedar al menos un administrador en el sistema.
        if ($usuario->esAdmin()
            && $datos['rol'] !== User::ROL_ADMIN
            && User::where('rol', User::ROL_ADMIN)->count() <= 1) {
            return back()->withInput()->with('error', __('hydropay.usuarios.ultimo_admin_rol'));
        }

        // Si no se escribió contraseña nueva se conserva la actual.
        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        // Guarda los cambios.
        $usuario->update($datos);

        // Si se cambió el rol, se revocan sus tokens de la API para que tome efecto de inmediato.
        if ($usuario->wasChanged('rol')) {
            $usuario->tokens()->delete();
        }

        // Bitácora.
        Log::info('Usuario actualizado por administrador', [
            'admin_id' => $request->user()->id,
            'user_id' => $usuario->id,
            'rol' => $usuario->rol,
        ]);

        // Regresa al listado.
        return redirect()->route('usuarios.index')->with('mensaje', __('hydropay.usuarios.actualizado'));
    }

    // DELETE /usuarios/{usuario} → elimina un usuario (no a sí mismo ni al último administrador).
    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        // Nadie puede eliminar su propia cuenta.
        if ($usuario->is($request->user())) {
            return redirect()->route('usuarios.index')->with('error', __('hydropay.usuarios.no_eliminarse'));
        }

        // Evita dejar el sistema sin administradores.
        if ($usuario->esAdmin() && User::where('rol', User::ROL_ADMIN)->count() <= 1) {
            return redirect()->route('usuarios.index')->with('error', __('hydropay.usuarios.ultimo_admin_eliminar'));
        }

        // Revoca sus tokens de la API.
        $usuario->tokens()->delete();

        // Elimina la cuenta; los pagos que registró se conservan con user_id nulo (nullOnDelete).
        $usuario->delete();

        // Bitácora.
        Log::warning('Usuario eliminado', ['admin_id' => $request->user()->id, 'user_id' => $usuario->id]);

        // Regresa al listado.
        return redirect()->route('usuarios.index')->with('mensaje', __('hydropay.usuarios.eliminado'));
    }
}
