{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Zonas')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado. --}}
<div class="page-heading">
    <div>
        <h1>Zonas</h1>
        <p class="muted">Pueblos, colonias y rancherías donde se presta el servicio.</p>
    </div>
    <a href="{{ route('zonas.create') }}" class="btn btn-primary">+ Nueva zona</a>
</div>

<section class="panel">
    {{-- Búsqueda por municipio o localidad. --}}
    <form action="{{ route('zonas.index') }}" method="GET" class="search-form" role="search">
        <input type="search" name="busqueda" value="{{ $busqueda }}" placeholder="Buscar por municipio o localidad..." aria-label="Buscar zonas">
        <button type="submit" class="btn btn-secondary">Buscar</button>
        @if ($busqueda !== '')
            <a href="{{ route('zonas.index') }}" class="btn btn-light">Limpiar</a>
        @endif
    </form>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Localidad</th><th>Municipio</th><th class="num">Clientes</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                {{-- @forelse recorre la lista o muestra la fila vacía. --}}
                @forelse ($zonas as $zona)
                    <tr>
                        <td><strong>{{ $zona->localidad }}</strong></td>
                        <td>{{ $zona->municipio }}</td>
                        {{-- Enlace al listado de clientes filtrado por esta zona. --}}
                        <td class="num"><a href="{{ route('clientes.index', ['zona' => $zona->id]) }}">{{ $zona->clientes_count }}</a></td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('zonas.edit', $zona) }}" class="btn btn-light btn-small">Editar</a>
                                {{-- Solo se ofrece eliminar si la zona no tiene clientes. --}}
                                @if ($zona->clientes_count === 0)
                                    <x-boton-eliminar :action="route('zonas.destroy', $zona)" pregunta="¿Deseas eliminar esta zona?" />
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty-state">Todavía no hay zonas. Registra la primera con "+ Nueva zona".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación. --}}
    <div class="paginacion">{{ $zonas->links() }}</div>
</section>

@endsection
