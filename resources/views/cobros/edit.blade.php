{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Editar cobro')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado. --}}
<div class="page-heading">
    <div>
        <h1>Editar cobro</h1>
        <p class="muted">{{ $cobro->periodo }}, estado actual <x-estado-cobro :estado="$cobro->estado" /></p>
    </div>
    <a href="{{ route('cobros.show', $cobro) }}" class="btn btn-light">Volver al detalle</a>
</div>

<section class="panel">
    {{-- FORMULARIO: PUT /cobros/{cobro} → CobroController@update. --}}
    <form action="{{ route('cobros.update', $cobro) }}" method="POST" novalidate>
        {{-- Token CSRF. --}}
        @csrf
        {{-- Método PUT simulado. --}}
        @method('PUT')

        {{-- Campos compartidos. --}}
        @include('cobros._form')

        {{-- Botones. --}}
        <div class="form-actions">
            <a href="{{ route('cobros.show', $cobro) }}" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
</section>

@endsection
