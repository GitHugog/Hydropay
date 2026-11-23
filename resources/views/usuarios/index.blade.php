{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Usuarios')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado. --}}
<div class="page-heading">
    <div>
        <h1>Usuarios y roles</h1>
        <p class="muted">Quién entra al sistema y qué puede hacer.</p>
    </div>
    <a href="{{ route('usuarios.create') }}" class="btn btn-primary">+ Nuevo usuario</a>
</div>

<section class="panel">
    {{-- Búsqueda por nombre o correo. --}}
    <form action="{{ route('usuarios.index') }}" method="GET" class="search-form" role="search">
        <input type="search" name="busqueda" value="{{ $busqueda }}" placeholder="Buscar por nombre o correo..." aria-label="Buscar usuarios">
        <button type="submit" class="btn btn-secondary">Buscar</button>
        @if ($busqueda !== '')
            <a href="{{ route('usuarios.index') }}" class="btn btn-light">Limpiar</a>
        @endif
    </form>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                {{-- @forelse recorre la lista o muestra la fila vacía. --}}
                @forelse ($usuarios as $usuario)
                    <tr>
                        <td>{{ $usuario->name }} @if ($usuario->is(auth()->user())) <span class="hint">(tú)</span> @endif</td>
                        <td>{{ $usuario->email }}</td>
                        <td>
                            <span class="badge {{ $usuario->esAdmin() ? 'badge-info' : 'badge-muted' }}">{{ __('hydropay.roles.'.$usuario->rol) }}</span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-light btn-small">Editar</a>
                                {{-- Nadie puede eliminar su propia cuenta. --}}
                                @unless ($usuario->is(auth()->user()))
                                    <x-boton-eliminar :action="route('usuarios.destroy', $usuario)" pregunta="¿Deseas eliminar este usuario? Sus pagos registrados se conservan." />
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty-state">No se encontraron usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación. --}}
    <div class="paginacion">{{ $usuarios->links() }}</div>
</section>

@endsection
