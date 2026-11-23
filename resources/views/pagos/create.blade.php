{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Registrar pago completo')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado. --}}
<div class="page-heading">
    <div>
        <h1>Registrar pago completo</h1>
        <p class="muted">Cada recibo se liquida en una sola operación. No se aceptan abonos.</p>
    </div>
    <a href="{{ route('pagos.index') }}" class="btn btn-light">Volver al historial</a>
</div>

<section class="panel">
    {{-- Si no hay recibos por cobrar se muestra un estado vacío. --}}
    @if ($cobros->isEmpty())
        <div class="empty-state">
            <h2>No hay recibos disponibles para cobrar</h2>
            <p>Todos los recibos están pagados, cancelados o todavía no se han registrado.</p>
            <a href="{{ route('cobros.index') }}" class="btn btn-secondary">Consultar recibos</a>
        </div>
    @else
        {{-- FORMULARIO: POST /pagos → PagoController@store. enctype permite adjuntar el comprobante. --}}
        <form action="{{ route('pagos.store') }}" method="POST" enctype="multipart/form-data" novalidate>
            {{-- Token CSRF. --}}
            @csrf

            <div class="form-grid">
                {{-- Recibo a liquidar: solo pendientes o vencidos sin pago. --}}
                <div class="field field-full">
                    <label for="cobro_id">Recibo pendiente o vencido *</label>
                    <select id="cobro_id" name="cobro_id" required data-monto-origen @error('cobro_id') aria-invalid="true" @enderror>
                        <option value="" data-monto="">Selecciona el recibo que se va a liquidar</option>
                        @foreach ($cobros as $cobro)
                            {{-- data-monto alimenta el aviso "Importe a cobrar" (hydropay.js). --}}
                            <option value="{{ $cobro->id }}" data-monto="{{ formato_moneda($cobro->monto) }}"
                                @selected((string) old('cobro_id', $cobroId) === (string) $cobro->id)>
                                {{ $cobro->cliente->nombre }} ({{ $cobro->cliente->numero_cuenta }}), {{ $cobro->periodo }},
                                {{ mb_strtolower(etiqueta_estado($cobro->estado)) }}, total {{ formato_moneda($cobro->monto) }}
                            </option>
                        @endforeach
                    </select>
                    @error('cobro_id') <span class="error-text">{{ $message }}</span> @enderror
                    {{-- Aviso dinámico con el importe exacto que se registrará. --}}
                    <p class="importe-cobrar" data-monto-destino aria-live="polite">El sistema registra exactamente el importe del recibo seleccionado.</p>
                    @error('monto') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                {{-- Método de pago. --}}
                <div class="field">
                    <label for="metodo">Método de pago *</label>
                    <select id="metodo" name="metodo" required @error('metodo') aria-invalid="true" @enderror>
                        <option value="">Selecciona un método</option>
                        @foreach ($metodos as $opcion)
                            <option value="{{ $opcion }}" @selected(old('metodo') === $opcion)>{{ __('hydropay.metodos.'.$opcion) }}</option>
                        @endforeach
                    </select>
                    @error('metodo') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                {{-- Fecha del pago (no futura). --}}
                <div class="field">
                    <label for="fecha_pago">Fecha de pago *</label>
                    <input id="fecha_pago" type="date" name="fecha_pago" max="{{ now()->format('Y-m-d') }}" required
                           value="{{ old('fecha_pago', now()->format('Y-m-d')) }}" @error('fecha_pago') aria-invalid="true" @enderror>
                    @error('fecha_pago') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                {{-- Referencia opcional. --}}
                <div class="field">
                    <label for="referencia">Referencia o folio</label>
                    <input id="referencia" type="text" name="referencia" maxlength="100" value="{{ old('referencia') }}"
                           placeholder="Número de operación bancaria, folio de caja...">
                    <span class="hint">En transferencias o depósitos verifica la acreditación antes de registrar.</span>
                    @error('referencia') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                {{-- Comprobante opcional (se guarda en almacenamiento privado). --}}
                <div class="field">
                    <label for="comprobante">Comprobante (JPG, PNG o PDF, máximo 2 MB)</label>
                    <input id="comprobante" type="file" name="comprobante" accept=".jpg,.jpeg,.png,.pdf">
                    @error('comprobante') <span class="error-text">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Botones. --}}
            <div class="form-actions">
                <a href="{{ route('pagos.index') }}" class="btn btn-light">Cancelar</a>
                <button type="submit" class="btn btn-primary">Confirmar pago completo</button>
            </div>
        </form>
    @endif
</section>

@endsection
