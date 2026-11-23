{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Clientes')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado del módulo y botón de alta. --}}
<div class="page-heading">
    <div>
        <h1>Clientes</h1>
    </div>

    {{-- Solo administrador y cajero ven el botón (Policy create). --}}
    @can('create', \App\Models\Cliente::class)
        <a href="{{ route('clientes.create') }}" class="btn btn-primary">+ Nuevo cliente</a>
    @endcan
</div>

<section class="panel">

    {{-- BUSCADOR (GET): los filtros quedan en la URL y se conservan al paginar. --}}
    <form action="{{ route('clientes.index') }}" method="GET" class="search-form" role="search">
        {{-- Texto libre. --}}
        <input type="search" name="busqueda" value="{{ $busqueda }}"
               placeholder="Buscar por nombre, cuenta o dirección..." aria-label="Buscar clientes">

        {{-- Filtro por estado del registro. --}}
        <select name="estado" aria-label="Filtrar por estado">
            <option value="">Todos</option>
            <option value="activos" @selected($estado === 'activos')>Activos</option>
            <option value="inactivos" @selected($estado === 'inactivos')>Inactivos</option>
        </select>

        {{-- Filtro por zona (pueblo o localidad). --}}
        <select name="zona" aria-label="Filtrar por zona">
            <option value="">Todas las zonas</option>
            @foreach ($zonas as $opcionZona)
                <option value="{{ $opcionZona->id }}" @selected($zona === (string) $opcionZona->id)>{{ $opcionZona->localidad }} — {{ $opcionZona->municipio }}</option>
            @endforeach
        </select>

        {{-- Aplicar filtros. --}}
        <button type="submit" class="btn btn-secondary">Buscar</button>

        {{-- Quitar filtros (solo si hay alguno). --}}
        @if ($busqueda !== '' || $estado !== '' || $zona !== '')
            <a href="{{ route('clientes.index') }}" class="btn btn-light">Limpiar</a>
        @endif
    </form>

    @if ($clientes->count() > 0)
        {{-- Tabla con desplazamiento horizontal en pantallas pequeñas. --}}
        <div class="table-wrapper">
            {{-- En celular la tabla se compacta: cuenta, nombre, adeudo y "Ver" (clase tabla-movil). --}}
            <table class="tabla-movil">
                <thead>
                    <tr>
                        <th>Cuenta</th>
                        <th>Nombre</th>
                        <th class="ocultar-movil">Zona</th>
                        <th class="ocultar-movil">Cuota</th>
                        <th class="num">Debe</th>
                        <th class="num ocultar-movil">Recibos</th>
                        <th class="ocultar-movil">Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clientes as $cliente)
                        <tr>
                            <td><strong>{{ $cliente->numero_cuenta }}</strong></td>
                            <td>
                                {{ $cliente->nombre }}
                                {{-- En celular la zona aparece bajo el nombre (su columna se oculta). --}}
                                @if ($cliente->zona)
                                    <small class="solo-movil">{{ $cliente->zona->localidad }}</small>
                                @endif
                            </td>
                            {{-- Localidad y municipio (o "Sin zona"). --}}
                            <td class="ocultar-movil">
                                @if ($cliente->zona)
                                    {{ $cliente->zona->localidad }} <small class="muted">{{ $cliente->zona->municipio }}</small>
                                @else
                                    <span class="muted">Sin zona</span>
                                @endif
                            </td>
                            {{-- Cuota y periodicidad (o "Sin cuota" si no se ha asignado). --}}
                            <td class="ocultar-movil">
                                @if ($cliente->tieneCuota())
                                    {{ formato_moneda((float) $cliente->tarifa) }}
                                    <small class="muted">{{ mb_strtolower(__('hydropay.periodicidades.'.$cliente->periodicidad)) }}</small>
                                @else
                                    <span class="muted">Sin cuota</span>
                                @endif
                            </td>
                            {{-- Suma de recibos pendientes y vencidos sin pago. --}}
                            <td class="num {{ (float) $cliente->adeudo_total > 0 ? 'money-negative' : '' }}">
                                {{ formato_moneda((float) $cliente->adeudo_total) }}
                            </td>
                            <td class="num ocultar-movil">{{ $cliente->cobros_count }}</td>
                            <td class="ocultar-movil">
                                {{-- Activo/Inactivo describe el registro, no si debe dinero. --}}
                                <span class="badge {{ $cliente->activo ? 'badge-success' : 'badge-muted' }}">
                                    {{ $cliente->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    {{-- Ver detalle (todos los roles). --}}
                                    <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-secondary btn-small">Ver</a>

                                    {{-- Editar (admin y cajero). --}}
                                    @can('update', $cliente)
                                        <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-light btn-small ocultar-movil">Editar</a>
                                    @endcan

                                    {{-- Eliminar: solo administrador y solo si no tiene recibos. --}}
                                    @if ($cliente->cobros_count === 0)
                                        @can('delete', $cliente)
                                            <x-boton-eliminar :action="route('clientes.destroy', $cliente)" pregunta="¿Deseas eliminar este cliente?" />
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Enlaces de paginación (vista vendor/pagination/hydropay). --}}
        <div class="paginacion">{{ $clientes->links() }}</div>
    @else
        {{-- Estado vacío. --}}
        <div class="empty-state">
            <h2>No hay clientes para mostrar</h2>
            <p>
                @if ($busqueda !== '' || $estado !== '')
                    No se encontraron resultados con esos filtros.
                @else
                    Registra el primer cliente para comenzar a utilizar HydroPay.
                @endif
            </p>
            @if ($busqueda === '' && $estado === '')
                @can('create', \App\Models\Cliente::class)
                    <a href="{{ route('clientes.create') }}" class="btn btn-primary">Registrar primer cliente</a>
                @endcan
            @endif
        </div>
    @endif
</section>

@endsection
