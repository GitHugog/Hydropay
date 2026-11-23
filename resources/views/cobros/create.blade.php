{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Nuevo cobro')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado. --}}
<div class="page-heading">
    <div>
        <h1>Registrar cobro</h1>
        <p class="muted">Solo para clientes activos. Los campos con * son obligatorios.</p>
    </div>
    <a href="{{ route('cobros.index') }}" class="btn btn-light">Volver al listado</a>
</div>

<section class="panel">
    {{-- Si no hay clientes activos no tiene caso mostrar el formulario. --}}
    @if ($clientes->isEmpty())
        <div class="empty-state">
            <h2>No hay clientes activos</h2>
            <p>Registra o activa un cliente antes de emitir recibos.</p>
            <a href="{{ route('clientes.create') }}" class="btn btn-primary">Registrar cliente</a>
        </div>
    @else
        {{-- FORMULARIO: POST /cobros → CobroController@store. --}}
        <form action="{{ route('cobros.store') }}" method="POST" novalidate>
            {{-- Token CSRF. --}}
            @csrf

            {{-- Campos compartidos. --}}
            @include('cobros._form')

            {{-- Botones. --}}
            <div class="form-actions">
                <a href="{{ route('cobros.index') }}" class="btn btn-light">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar cobro</button>
            </div>
        </form>
    @endif
</section>

@endsection
