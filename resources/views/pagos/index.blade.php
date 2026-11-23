{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Pagos')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado. --}}
<div class="page-heading">
    <div>
        <h1>Pagos</h1>
        <p class="muted">Cada recibo se paga completo y una sola vez.</p>
    </div>
    @can('create', \App\Models\Pago::class)
        <a href="{{ route('pagos.create') }}" class="btn btn-primary">+ Registrar pago</a>
    @endcan
</div>

<section class="panel">

    {{-- Búsqueda por referencia, cliente o cuenta y filtro por método. --}}
    <form action="{{ route('pagos.index') }}" method="GET" class="search-form" role="search">
        <input type="search" name="busqueda" value="{{ $busqueda }}"
               placeholder="Buscar por referencia, cliente o cuenta..." aria-label="Buscar pagos">

        <select name="metodo" aria-label="Filtrar por método">
            <option value="">Todos los métodos</option>
            @foreach ($metodos as $opcion)
                <option value="{{ $opcion }}" @selected($metodo === $opcion)>{{ __('hydropay.metodos.'.$opcion) }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-secondary">Filtrar</button>

        @if ($busqueda !== '' || $metodo !== '')
            <a href="{{ route('pagos.index') }}" class="btn btn-light">Limpiar</a>
        @endif
    </form>

    @if ($pagos->count() > 0)
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Periodo</th>
                        <th class="num">Monto</th>
                        <th>Método</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pagos as $pago)
                        <tr>
                            <td>#{{ $pago->id }}</td>
                            <td>{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                            <td>{{ $pago->cobro->cliente->nombre }}</td>
                            <td>{{ $pago->cobro->periodo }}</td>
                            <td class="num">{{ formato_moneda($pago->monto) }}</td>
                            <td>{{ __('hydropay.metodos.'.$pago->metodo) }}</td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('pagos.show', $pago) }}" class="btn btn-secondary btn-small">Ver</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginación. --}}
        <div class="paginacion">{{ $pagos->links() }}</div>
    @else
        <div class="empty-state">
            <h2>No hay pagos para mostrar</h2>
            <p>No se encontraron pagos con los filtros indicados.</p>
        </div>
    @endif
</section>

@endsection
