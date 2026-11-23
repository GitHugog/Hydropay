{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Detalle del cliente')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado con acciones. --}}
<div class="page-heading">
    <div>
        <h1>{{ $cliente->nombre }}</h1>
        <p class="muted">Cuenta: {{ $cliente->numero_cuenta }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('clientes.index') }}" class="btn btn-light">Volver</a>
        {{-- Editar (admin y cajero). --}}
        @can('update', $cliente)
            <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-primary">Editar cliente</a>
        @endcan
    </div>
</div>

{{-- Indicadores del cliente. --}}
<div class="cifras">
    <x-indicador etiqueta="Adeudo actual" :valor="formato_moneda($adeudo)" :clase-valor="$adeudo > 0 ? 'money-negative' : 'money-positive'" />
    <x-indicador etiqueta="Recibos registrados" :valor="$cliente->cobros->count()" />
    <x-indicador etiqueta="Recibos pagados" :valor="$cliente->cobros->where('estado', 'pagado')->count()" />
</div>

{{-- Datos generales. --}}
<section class="panel">
    <h2>Información general</h2>
    <div class="detail-grid">
        <div class="detail-item"><small>Número de cuenta</small><strong>{{ $cliente->numero_cuenta }}</strong></div>
        <div class="detail-item"><small>Estado del registro</small><strong>{{ $cliente->activo ? 'Activo' : 'Inactivo' }}</strong></div>
        <div class="detail-item"><small>Periodicidad del cobro</small><strong>{{ __('hydropay.periodicidades.'.($cliente->periodicidad ?: 'mensual')) }}</strong></div>
        <div class="detail-item"><small>Cuota por periodo</small><strong>{{ $cliente->tieneCuota() ? formato_moneda((float) $cliente->tarifa) : 'Sin asignar (recibos manuales)' }}</strong></div>
        <div class="detail-item"><small>Dirección</small><strong>{{ $cliente->direccion }}</strong></div>
        <div class="detail-item"><small>Zona</small><strong>{{ $cliente->zona?->nombreCompleto() ?? 'Sin zona' }}</strong></div>
        <div class="detail-item"><small>Teléfono</small><strong>{{ $cliente->telefono ?: 'No registrado' }}</strong></div>
        <div class="detail-item"><small>Correo electrónico</small><strong>{{ $cliente->email ?: 'No registrado' }}</strong></div>
        <div class="detail-item"><small>Fecha de registro</small><strong>{{ $cliente->created_at?->format('d/m/Y') }}</strong></div>
    </div>
</section>

{{-- Recibos del cliente. --}}
<section class="panel">
    <div class="page-heading">
        <div>
            <h2>Recibos asociados</h2>
            <p class="muted">Periodos e importes registrados para esta cuenta.</p>
        </div>
        {{-- Nuevo recibo para este cliente (parámetro opcional ?cliente_id=). --}}
        @if ($cliente->activo)
            @can('create', \App\Models\Cobro::class)
                <a href="{{ route('cobros.create', ['cliente_id' => $cliente->id]) }}" class="btn btn-primary">+ Nuevo cobro</a>
            @endcan
        @endif
    </div>

    @if ($cliente->cobros->isNotEmpty())
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Periodo</th><th class="num">Monto</th><th>Vencimiento</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    @foreach ($cliente->cobros as $cobro)
                        <tr>
                            <td><a href="{{ route('cobros.show', $cobro) }}">{{ $cobro->periodo }}</a></td>
                            <td class="num">{{ formato_moneda($cobro->monto) }}</td>
                            <td>{{ $cobro->fecha_vencimiento?->format('d/m/Y') ?? 'Sin fecha' }}</td>
                            <td><x-estado-cobro :estado="$cobro->estado" /></td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('cobros.show', $cobro) }}" class="btn btn-secondary btn-small">Ver</a>
                                    {{-- Cobrar desde la ficha del cliente (admin/cajero, solo pendientes o vencidos). --}}
                                    @if (in_array($cobro->estado, ['pendiente', 'vencido'], true))
                                        @can('create', \App\Models\Pago::class)
                                            <a href="{{ route('pagos.create', ['cobro_id' => $cobro->id]) }}" class="btn btn-primary btn-small">Cobrar</a>
                                        @endcan
                                    @endif
                                    {{-- Acceso directo para cobrar recibos pendientes o vencidos. --}}
                                    @if (in_array($cobro->estado, ['pendiente', 'vencido'], true))
                                        @can('create', \App\Models\Pago::class)
                                            <a href="{{ route('pagos.create', ['cobro_id' => $cobro->id]) }}" class="btn btn-primary btn-small">Cobrar</a>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="empty-state">Este cliente todavía no tiene recibos registrados.</p>
    @endif
</section>

@endsection
