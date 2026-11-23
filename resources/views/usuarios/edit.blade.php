{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Editar usuario')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado. --}}
<div class="page-heading">
    <div>
        <h1>Editar usuario</h1>
        <p class="muted">{{ $usuario->email }}</p>
    </div>
    <a href="{{ route('usuarios.index') }}" class="btn btn-light">Volver al listado</a>
</div>

<section class="panel">
    {{-- FORMULARIO: PUT /usuarios/{usuario} → UsuarioController@update. --}}
    <form action="{{ route('usuarios.update', $usuario) }}" method="POST" novalidate>
        {{-- Token CSRF. --}}
        @csrf
        {{-- Método PUT simulado. --}}
        @method('PUT')
        {{-- Campos compartidos. --}}
        @include('usuarios._form')
        {{-- Botones. --}}
        <div class="form-actions">
            <a href="{{ route('usuarios.index') }}" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
</section>

@endsection
