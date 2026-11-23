{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título de la pantalla. --}}
@section('titulo', 'Inicio')

{{-- Contenido del panel. --}}
@section('contenido')

{{-- Título con la fecha de hoy (helper nombre_mes en app/Support/helpers.php). --}}
<div class="page-heading">
    <div>
        <h1>Inicio</h1>
        <p class="muted">Hoy es {{ now()->day }} de {{ nombre_mes(now()->month) }} de {{ now()->year }}.</p>
    </div>
</div>

{{-- BUSCADOR PRINCIPAL: lo primero que hace el cajero es encontrar al cliente que llega a pagar. --}}
<form action="{{ route('clientes.index') }}" method="GET" class="buscador-principal" role="search">
    <label for="buscar-cliente">Buscar cliente</label>
    <div class="buscador-fila">
        <input id="buscar-cliente" type="search" name="busqueda" placeholder="Nombre, número de cuenta o dirección" autocomplete="off">
        <button type="submit" class="btn btn-primary">Buscar</button>
    </div>
    <p class="hint">
        Hay <a href="{{ route('clientes.index') }}">{{ $resumen['clientes'] }} clientes registrados</a>
        y <a href="{{ route('pagos.index') }}">{{ $resumen['pagos'] }} pagos</a> en el historial.
    </p>
</form>

{{-- CIFRAS DEL SERVICIO (ResumenService: Cache + Collections). Cada cifra lleva a su listado. --}}
<section class="cifras" aria-label="Cifras del servicio">
    <x-indicador etiqueta="Por cobrar" :valor="formato_moneda($resumen['por_cobrar'])" :clase-valor="$resumen['por_cobrar'] > 0 ? 'money-negative' : ''" nota="Ver reporte del mes" :href="route('reportes.index')" />
    <x-indicador etiqueta="Cobrado" :valor="formato_moneda($resumen['cobrado'])" nota="Ver pagos" :href="route('pagos.index')" />
    <x-indicador etiqueta="Cobros pendientes" :valor="$resumen['pendientes']" nota="Ver pendientes" :href="route('cobros.index', ['estado' => 'pendiente'])" />
    <x-indicador etiqueta="Cobros vencidos" :valor="$resumen['vencidos']" :clase-valor="$resumen['vencidos'] > 0 ? 'money-negative' : ''" nota="Ver vencidos" :href="route('cobros.index', ['estado' => 'vencido'])" />
</section>

{{-- Acciones frecuentes: solo para quien puede registrar (Policy create). --}}
@can('create', \App\Models\Cliente::class)
    <div class="acciones-rapidas">
        <a href="{{ route('pagos.create') }}" class="btn btn-primary">Registrar pago</a>
        <a href="{{ route('clientes.create') }}" class="btn btn-secondary">Registrar cliente</a>
        <a href="{{ route('cobros.create') }}" class="btn btn-secondary">Registrar cobro</a>
    </div>
@endcan

{{-- Dos listas lado a lado en escritorio y una debajo de otra en celular. --}}
<div class="grid-2">
    {{-- Últimos pagos. --}}
    <section class="panel">
        <h2>Últimos pagos</h2>
        @if ($ultimosPagos->isNotEmpty())
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>Fecha</th><th>Cliente</th><th>Periodo</th><th class="num">Monto</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($ultimosPagos as $pago)
                            <tr>
                                <td><a href="{{ route('pagos.show', $pago) }}">{{ $pago->fecha_pago->format('d/m/Y') }}</a></td>
                                <td>{{ $pago->cobro->cliente->nombre }}</td>
                                <td>{{ $pago->cobro->periodo }}</td>
                                <td class="num">{{ formato_moneda($pago->monto) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="empty-state">Todavía no hay pagos. Cuando registres uno aparecerá aquí.</p>
        @endif
    </section>

    {{-- Recibos vencidos para seguimiento. --}}
    <section class="panel">
        <h2>Cobros vencidos</h2>
        @if ($vencidos->isNotEmpty())
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>Cliente</th><th>Periodo</th><th>Venció</th><th class="num">Monto</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($vencidos as $cobro)
                            <tr>
                                <td>{{ $cobro->cliente->nombre }}</td>
                                <td><a href="{{ route('cobros.show', $cobro) }}">{{ $cobro->periodo }}</a></td>
                                <td>{{ $cobro->fecha_vencimiento?->format('d/m/Y') }}</td>
                                <td class="num money-negative">{{ formato_moneda($cobro->monto) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="empty-state">No hay cobros vencidos.</p>
        @endif
    </section>
</div>

@endsection
