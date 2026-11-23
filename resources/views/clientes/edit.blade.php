{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Editar cliente')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado de la pantalla. --}}
<div class="page-heading">
    <div>
        <h1>Editar cliente</h1>
        <p class="muted">{{ $cliente->nombre }}, cuenta {{ $cliente->numero_cuenta }}</p>
    </div>
    <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-light">Volver al detalle</a>
</div>

<section class="panel">
    {{-- FORMULARIO: PUT /clientes/{cliente} → ClienteController@update. --}}
    <form action="{{ route('clientes.update', $cliente) }}" method="POST" novalidate>
        {{-- Token CSRF. --}}
        @csrf
        {{-- Los formularios HTML solo envían POST; este campo indica a Laravel que es PUT. --}}
        @method('PUT')

        {{-- Campos compartidos con el alta. --}}
        @include('clientes._form')

        {{-- Botones. --}}
        <div class="form-actions">
            <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
</section>

@endsection
