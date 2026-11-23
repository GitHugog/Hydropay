{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Nuevo usuario')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado. --}}
<div class="page-heading">
    <div>
        <h1>Registrar usuario</h1>
        <p class="muted">Da de alta al personal del organismo y asígnale un rol.</p>
    </div>
    <a href="{{ route('usuarios.index') }}" class="btn btn-light">Volver al listado</a>
</div>

<section class="panel">
    {{-- FORMULARIO: POST /usuarios → UsuarioController@store. --}}
    <form action="{{ route('usuarios.store') }}" method="POST" novalidate>
        {{-- Token CSRF. --}}
        @csrf
        {{-- Campos compartidos. --}}
        @include('usuarios._form')
        {{-- Botones. --}}
        <div class="form-actions">
            <a href="{{ route('usuarios.index') }}" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar usuario</button>
        </div>
    </form>
</section>

@endsection
