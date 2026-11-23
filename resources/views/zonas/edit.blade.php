{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Editar zona')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado. --}}
<div class="page-heading">
    <div>
        <h1>Editar zona</h1>
        <p class="muted">{{ $zona->nombreCompleto() }}</p>
    </div>
    <a href="{{ route('zonas.index') }}" class="btn btn-light">Volver al listado</a>
</div>

<section class="panel">
    {{-- FORMULARIO: PUT /zonas/{zona} → ZonaController@update. --}}
    <form action="{{ route('zonas.update', $zona) }}" method="POST" novalidate>
        {{-- Token CSRF y método PUT. --}}
        @csrf
        @method('PUT')
        {{-- Campos compartidos. --}}
        @include('zonas._form')
        {{-- Botones. --}}
        <div class="form-actions">
            <a href="{{ route('zonas.index') }}" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
</section>

@endsection
