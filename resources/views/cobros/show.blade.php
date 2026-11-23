{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Detalle del cobro')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado con acciones. --}}
<div class="page-heading">
    <div>
        <h1>Cobro {{ $cobro->periodo }}</h1>
        <p class="muted">
            Cliente:
            <a href="{{ route('clientes.show', $cobro->cliente) }}"><strong>{{ $cobro->cliente->nombre }}</strong></a>,
            cuenta {{ $cobro->cliente->numero_cuenta }}
        </p>
    </div>

    <div class="actions">
        <a href="{{ route('cobros.index') }}" class="btn btn-light">Volver</a>

        {{-- Editar: la Policy decide (no pagado; cancelado solo admin). --}}
        @can('update', $cobro)
            <a href="{{ route('cobros.edit', $cobro) }}" class="btn btn-light">Editar</a>
        @endcan

        {{-- Registrar el pago completo (solo pendientes o vencidos). --}}
        @if (in_array($cobro->estado, ['pendiente', 'vencido'], true))
            @can('create', \App\Models\Pago::class)
                <a href="{{ route('pagos.create', ['cobro_id' => $cobro->id]) }}" class="btn btn-primary">+ Registrar pago</a>
            @endcan
        @endif
    </div>
</div>

{{-- Información del recibo. --}}
<section class="panel">
    <h2>Información del cobro</h2>
    <div class="detail-grid">
        <div class="detail-item"><small>Estado</small><strong><x-estado-cobro :estado="$cobro->estado" /></strong></div>
        <div class="detail-item"><small>Monto</small><strong>{{ formato_moneda($cobro->monto) }}</strong></div>
        <div class="detail-item"><small>Pago recibido</small><strong class="money-positive">{{ formato_moneda($cobro->totalPagado()) }}</strong></div>
        <div class="detail-item"><small>Importe por liquidar</small><strong>{{ formato_moneda($cobro->saldo()) }}</strong></div>
        <div class="detail-item"><small>Fecha de vencimiento</small><strong>{{ $cobro->fecha_vencimiento?->format('d/m/Y') ?? 'Sin fecha' }}</strong></div>
        <div class="detail-item"><small>Descripción</small><strong>{{ $cobro->descripcion ?: 'Sin descripción' }}</strong></div>
    </div>
</section>

{{-- Pago del recibo (como máximo uno). --}}
<section class="panel">
    <h2>Pago registrado</h2>

    @if ($cobro->pago)
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Fecha</th><th class="num">Monto</th><th>Método</th><th>Referencia</th><th>Registró</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $cobro->pago->fecha_pago->format('d/m/Y') }}</td>
                        <td class="num">{{ formato_moneda($cobro->pago->monto) }}</td>
                        <td>{{ __('hydropay.metodos.'.$cobro->pago->metodo) }}</td>
                        <td>{{ $cobro->pago->referencia ?: '—' }}</td>
                        {{-- Si el usuario se eliminó, el pago se conserva con user_id nulo. --}}
                        <td>{{ $cobro->pago->registradoPor?->name ?? 'Usuario eliminado' }}</td>
                        <td><a href="{{ route('pagos.show', $cobro->pago) }}" class="btn btn-secondary btn-small">Ver</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @else
        <p class="muted">Este recibo todavía no tiene un pago registrado.</p>
    @endif
</section>

@endsection
