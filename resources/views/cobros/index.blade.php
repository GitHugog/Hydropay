{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Cobros')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado y botón de alta. --}}
<div class="page-heading">
    <div>
        <h1>Cobros</h1>
    </div>
    <div class="actions">
        {{-- Administrador: crea el recibo del periodo actual a todos los clientes con cuota. --}}
        @if (auth()->user()->esAdmin())
            <form action="{{ route('cobros.generar') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-secondary">Generar recibos del periodo</button>
            </form>
        @endif
        @can('create', \App\Models\Cobro::class)
            <a href="{{ route('cobros.create') }}" class="btn btn-primary">+ Nuevo cobro</a>
        @endcan
    </div>
</div>

<section class="panel">

    {{-- Búsqueda y filtro por estado (GET para conservarlos en la URL). --}}
    <form action="{{ route('cobros.index') }}" method="GET" class="search-form" role="search">
        <input type="search" name="busqueda" value="{{ $busqueda }}"
               placeholder="Buscar por cliente, cuenta o periodo..." aria-label="Buscar cobros">

        <select name="estado" aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            @foreach ($estados as $opcion)
                <option value="{{ $opcion }}" @selected($estado === $opcion)>{{ etiqueta_estado($opcion) }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-secondary">Filtrar</button>

        @if ($busqueda !== '' || $estado !== '')
            <a href="{{ route('cobros.index') }}" class="btn btn-light">Limpiar</a>
        @endif
    </form>

    @if ($cobros->count() > 0)
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Periodo</th>
                        <th class="num">Monto</th>
                        <th class="num">Pagado</th>
                        <th>Vence</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cobros as $cobro)
                        <tr>
                            <td>
                                <strong>{{ $cobro->cliente->nombre }}</strong><br>
                                <span class="hint">{{ $cobro->cliente->numero_cuenta }}</span>
                            </td>
                            <td>{{ $cobro->periodo }}</td>
                            <td class="num">{{ formato_moneda($cobro->monto) }}</td>
                            {{-- Importe recibido (cero si no se ha pagado). --}}
                            <td class="num">{{ formato_moneda($cobro->pago?->monto ?? 0) }}</td>
                            <td>{{ $cobro->fecha_vencimiento?->format('d/m/Y') ?? 'Sin fecha' }}</td>
                            <td><x-estado-cobro :estado="$cobro->estado" /></td>
                            <td>
                                <div class="actions">
                                    {{-- Ver (todos). --}}
                                    <a href="{{ route('cobros.show', $cobro) }}" class="btn btn-secondary btn-small">Ver</a>

                                    {{-- Cobrar (admin/cajero, solo pendientes o vencidos). --}}
                                    @if (in_array($cobro->estado, ['pendiente', 'vencido'], true))
                                        @can('create', \App\Models\Pago::class)
                                            <a href="{{ route('pagos.create', ['cobro_id' => $cobro->id]) }}" class="btn btn-primary btn-small">Cobrar</a>
                                        @endcan
                                    @endif

                                    {{-- Editar: CobroPolicy oculta el botón si está pagado o cancelado (salvo admin). --}}
                                    @can('update', $cobro)
                                        <a href="{{ route('cobros.edit', $cobro) }}" class="btn btn-light btn-small">Editar</a>
                                    @endcan

                                    {{-- Eliminar: solo administrador y sin pago. --}}
                                    @if (! $cobro->pago)
                                        @can('delete', $cobro)
                                            <x-boton-eliminar :action="route('cobros.destroy', $cobro)" pregunta="¿Deseas eliminar este cobro?" />
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginación. --}}
        <div class="paginacion">{{ $cobros->links() }}</div>
    @else
        <div class="empty-state">
            <h2>No hay cobros para mostrar</h2>
            <p>No se encontraron cobros con los filtros indicados.</p>
        </div>
    @endif
</section>

@endsection
