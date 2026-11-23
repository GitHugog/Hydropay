{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Nuevo cliente')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado de la pantalla. --}}
<div class="page-heading">
    <div>
        <h1>Registrar cliente</h1>
        <p class="muted">Los campos con * son obligatorios.</p>
    </div>
    <a href="{{ route('clientes.index') }}" class="btn btn-light">Volver al listado</a>
</div>

<section class="panel">
    {{-- FORMULARIO: POST /clientes → ClienteController@store. --}}
    <form action="{{ route('clientes.store') }}" method="POST" novalidate>
        {{-- Token CSRF obligatorio. --}}
        @csrf

        {{-- Campos compartidos con la edición. --}}
        @include('clientes._form')

        {{-- Botones. --}}
        <div class="form-actions">
            <a href="{{ route('clientes.index') }}" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar cliente</button>
        </div>
    </form>
</section>

@endsection
