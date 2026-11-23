{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título con el mes del reporte. --}}
@section('titulo', 'Reporte de '.$reporte['titulo'])

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado con selector de mes y exportación. --}}
<div class="page-heading">
    <div>
        <h1>Reporte mensual: {{ $reporte['titulo'] }}</h1>
        <p class="muted">{{ $reporte['organismo'] }}, del {{ $reporte['inicio']->format('d/m/Y') }} al {{ $reporte['fin']->format('d/m/Y') }}</p>
    </div>

    <div class="actions">
        {{-- Mes anterior y siguiente (rutas con parámetros opcionales anio/mes). --}}
        @php
            $anterior = $reporte['inicio']->copy()->subMonth();
            $siguiente = $reporte['inicio']->copy()->addMonth();
        @endphp
        <a href="{{ route('reportes.index', ['anio' => $anterior->year, 'mes' => $anterior->month]) }}" class="btn btn-light">‹ {{ ucfirst(nombre_mes($anterior->month)) }}</a>
        <a href="{{ route('reportes.index', ['anio' => $siguiente->year, 'mes' => $siguiente->month]) }}" class="btn btn-light">{{ ucfirst(nombre_mes($siguiente->month)) }} ›</a>
        {{-- Descarga en CSV (se abre en Excel). --}}
        <a href="{{ route('reportes.csv', ['anio' => $reporte['anio'], 'mes' => $reporte['mes']]) }}" class="btn btn-primary">Descargar CSV</a>
        {{-- Impresión o "Guardar como PDF" desde el navegador. --}}
        <button type="button" class="btn btn-secondary" data-imprimir>Imprimir</button>
    </div>
</div>

{{-- Selector de mes: envía ?periodo=AAAA-MM y el controlador redirige a /reportes/AAAA/MM. --}}
<form action="{{ route('reportes.index') }}" method="GET" class="search-form no-imprimir">
    <label for="periodo" class="sr-only">Mes del reporte</label>
    <input id="periodo" type="month" name="periodo" value="{{ sprintf('%04d-%02d', $reporte['anio'], $reporte['mes']) }}" required>
    <button type="submit" class="btn btn-secondary">Ver mes</button>
</form>

{{-- Indicadores principales del mes. --}}
<div class="cifras">
    <x-indicador etiqueta="Ingresos del mes" :valor="formato_moneda($reporte['ingresos']['total'])" clase-valor="money-positive" :nota="$reporte['ingresos']['cantidad'].' pagos recibidos'" />
    <x-indicador etiqueta="Adeudo vigente (todos los meses)" :valor="formato_moneda($reporte['cartera']['total'])" clase-valor="money-negative" :nota="$reporte['cartera']['cantidad'].' recibos por cobrar, '.$reporte['cartera']['vencidos'].' vencidos'" />
    <x-indicador etiqueta="Clientes registrados" :valor="$reporte['clientes']['total']" :nota="$reporte['clientes']['activos'].' activos, '.$reporte['clientes']['nuevos'].' nuevos este mes'" />
</div>

<div class="grid-2">
    {{-- Ingresos por método de pago (Collections: agrupación y suma). --}}
    <section class="panel">
        <h2>Ingresos por método de pago</h2>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Método</th><th class="num">Pagos</th><th class="num">Importe</th></tr></thead>
                <tbody>
                    @foreach ($reporte['por_metodo'] as $metodo => $fila)
                        <tr>
                            <td>{{ __('hydropay.metodos.'.$metodo) }}</td>
                            <td class="num">{{ $fila['cantidad'] }}</td>
                            <td class="num">{{ formato_moneda($fila['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><th>Total</th><th class="num">{{ $reporte['ingresos']['cantidad'] }}</th><th class="num">{{ formato_moneda($reporte['ingresos']['total']) }}</th></tr>
                </tfoot>
            </table>
        </div>
    </section>

    {{-- Recibos registrados en el mes por estado. --}}
    <section class="panel">
        <h2>Recibos registrados en el mes</h2>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Estado actual</th><th class="num">Recibos</th><th class="num">Importe</th></tr></thead>
                <tbody>
                    @foreach ($reporte['recibos']['por_estado'] as $estado => $fila)
                        <tr>
                            <td><x-estado-cobro :estado="$estado" /></td>
                            <td class="num">{{ $fila['cantidad'] }}</td>
                            <td class="num">{{ formato_moneda($fila['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><th>Total</th><th class="num">{{ $reporte['recibos']['cantidad'] }}</th><th class="num">{{ formato_moneda($reporte['recibos']['total']) }}</th></tr>
                </tfoot>
            </table>
        </div>
    </section>
</div>

{{-- Principales deudores con recibos vencidos (seguimiento de cobranza). --}}
<section class="panel">
    <h2>Clientes con mayor adeudo vencido</h2>
    @if ($reporte['deudores']->isNotEmpty())
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Cuenta</th><th>Cliente</th><th class="num">Recibos vencidos</th><th class="num">Adeudo vencido</th><th class="no-imprimir">Acciones</th></tr></thead>
                <tbody>
                    @foreach ($reporte['deudores'] as $fila)
                        <tr>
                            <td>{{ $fila['cliente']?->numero_cuenta }}</td>
                            <td>{{ $fila['cliente']?->nombre }}</td>
                            <td class="num">{{ $fila['recibos'] }}</td>
                            <td class="num">{{ formato_moneda($fila['adeudo']) }}</td>
                            <td class="no-imprimir">
                                @if ($fila['cliente'])
                                    <a href="{{ route('clientes.show', $fila['cliente']) }}" class="btn btn-secondary btn-small">Ver cliente</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="muted">No hay clientes con recibos vencidos.</p>
    @endif
</section>

{{-- Detalle de pagos del mes. --}}
<section class="panel">
    <h2>Detalle de pagos recibidos</h2>
    @if ($reporte['pagos']->isNotEmpty())
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Fecha</th><th>Folio</th><th>Cliente</th><th>Periodo</th><th>Método</th><th class="num">Importe</th></tr></thead>
                <tbody>
                    @foreach ($reporte['pagos'] as $pago)
                        <tr>
                            <td>{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                            <td><a href="{{ route('pagos.show', $pago) }}">#{{ $pago->id }}</a></td>
                            <td>{{ $pago->cobro->cliente->nombre }}</td>
                            <td>{{ $pago->cobro->periodo }}</td>
                            <td>{{ __('hydropay.metodos.'.$pago->metodo) }}</td>
                            <td class="num">{{ formato_moneda($pago->monto) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="muted">No se registraron pagos en este mes.</p>
    @endif
</section>

@endsection
