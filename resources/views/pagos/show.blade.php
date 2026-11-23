{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Detalle del pago')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado con acciones. --}}
<div class="page-heading">
    <div>
        <h1>Pago #{{ $pago->id }}</h1>
        <p class="muted">{{ $pago->cobro->cliente->nombre }}, {{ $pago->cobro->periodo }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('pagos.index') }}" class="btn btn-light">Volver</a>
        <a href="{{ route('cobros.show', $pago->cobro) }}" class="btn btn-secondary">Ver cobro</a>
        {{-- Imprime la pantalla como comprobante (los estilos @media print ocultan el menú). --}}
        <button type="button" class="btn btn-primary" data-imprimir>Imprimir</button>
    </div>
</div>

{{-- Datos del pago. --}}
<section class="panel">
    <div class="detail-grid">
        <div class="detail-item"><small>Monto</small><strong>{{ formato_moneda($pago->monto) }}</strong></div>
        <div class="detail-item"><small>Método</small><strong>{{ __('hydropay.metodos.'.$pago->metodo) }}</strong></div>
        <div class="detail-item"><small>Fecha de pago</small><strong>{{ $pago->fecha_pago->format('d/m/Y') }}</strong></div>
        <div class="detail-item"><small>Referencia</small><strong>{{ $pago->referencia ?: 'No registrada' }}</strong></div>
        <div class="detail-item"><small>Cuenta</small><strong>{{ $pago->cobro->cliente->numero_cuenta }}</strong></div>
        <div class="detail-item"><small>Registrado por</small><strong>{{ $pago->registradoPor?->name ?? 'Usuario eliminado' }}</strong></div>
        <div class="detail-item"><small>Estado del cobro</small><strong><x-estado-cobro :estado="$pago->cobro->estado" /></strong></div>
        <div class="detail-item">
            <small>Comprobante</small>
            <strong>
                @if ($pago->comprobante)
                    {{-- La descarga pasa por un controlador protegido (el archivo no es público). --}}
                    <a href="{{ route('pagos.comprobante', $pago) }}">Descargar comprobante</a>
                @else
                    No adjuntado
                @endif
            </strong>
        </div>
    </div>
</section>

@endsection
