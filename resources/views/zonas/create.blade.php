{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Nueva zona')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado. --}}
<div class="page-heading">
    <div>
        <h1>Registrar zona</h1>
        <p class="muted">Agrega un municipio y una localidad para asignarla a los clientes.</p>
    </div>
    <a href="{{ route('zonas.index') }}" class="btn btn-light">Volver al listado</a>
</div>

<section class="panel">
    {{-- FORMULARIO: POST /zonas → ZonaController@store. --}}
    <form action="{{ route('zonas.store') }}" method="POST" novalidate>
        {{-- Token CSRF. --}}
        @csrf
        {{-- Campos compartidos. --}}
        @include('zonas._form')
        {{-- Botones. --}}
        <div class="form-actions">
            <a href="{{ route('zonas.index') }}" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar zona</button>
        </div>
    </form>
</section>

@endsection
