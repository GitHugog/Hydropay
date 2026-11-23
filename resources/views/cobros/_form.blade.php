{{-- FORMULARIO REUTILIZABLE de recibos (se incluye en create y edit). --}}
@php
    // true cuando la vista recibe un recibo existente (pantalla de edición).
    $esEdicion = isset($cobro);
@endphp

<div class="form-grid">

    {{-- Cliente al que se le emite el recibo (solo activos, más el actual al editar). --}}
    <div class="field">
        <label for="cliente_id">Cliente *</label>
        <select id="cliente_id" name="cliente_id" required @error('cliente_id') aria-invalid="true" @enderror>
            <option value="">Selecciona un cliente</option>
            @foreach ($clientes as $cliente)
                {{-- Preselección: lo capturado (old), el cliente del recibo o el ?cliente_id= de la URL. --}}
                <option value="{{ $cliente->id }}"
                    @selected((string) old('cliente_id', $cobro->cliente_id ?? $clienteId ?? '') === (string) $cliente->id)>
                    {{ $cliente->nombre }} ({{ $cliente->numero_cuenta }})
                </option>
            @endforeach
        </select>
        @error('cliente_id') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- Periodo o concepto (único por cliente). --}}
    <div class="field">
        <label for="periodo">Periodo *</label>
        <input id="periodo" type="text" name="periodo" maxlength="30" required placeholder="Ej. Noviembre 2026"
               value="{{ old('periodo', $cobro->periodo ?? '') }}" @error('periodo') aria-invalid="true" @enderror>
        @error('periodo') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- Importe total del recibo. --}}
    <div class="field">
        <label for="monto">Monto ($) *</label>
        <input id="monto" type="number" name="monto" step="0.01" min="0.01" max="99999999.99" required inputmode="decimal"
               value="{{ old('monto', $cobro->monto ?? '') }}" @error('monto') aria-invalid="true" @enderror>
        <span class="hint">Importe informado por el organismo operador (máximo dos decimales).</span>
        @error('monto') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- Fecha límite de pago. --}}
    <div class="field">
        <label for="fecha_vencimiento">Fecha de vencimiento</label>
        <input id="fecha_vencimiento" type="date" name="fecha_vencimiento"
               value="{{ old('fecha_vencimiento', $esEdicion ? $cobro->fecha_vencimiento?->format('Y-m-d') : '') }}"
               @error('fecha_vencimiento') aria-invalid="true" @enderror>
        <span class="hint">Si la fecha ya pasó, el recibo se marcará como vencido.</span>
        @error('fecha_vencimiento') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- El estado solo se elige al editar; "pagado" y "vencido" los calcula el sistema. --}}
    @if ($esEdicion)
        <div class="field">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                {{-- "Sin pagar": el sistema lo cambiará a vencido al pasar la fecha. --}}
                <option value="pendiente" @selected(old('estado', $cobro->estado === 'cancelado' ? 'cancelado' : 'pendiente') === 'pendiente')>
                    Sin pagar (se vuelve vencido al pasar la fecha)
                </option>
                {{-- Solo el administrador puede cancelar un recibo. --}}
                @if (auth()->user()->esAdmin())
                    <option value="cancelado" @selected(old('estado', $cobro->estado) === 'cancelado')>Cancelado</option>
                @endif
            </select>
            <span class="hint">"Pagado" se asigna al registrar el pago completo.</span>
            @error('estado') <span class="error-text">{{ $message }}</span> @enderror
        </div>
    @endif

    {{-- Observaciones (ocupa las dos columnas). --}}
    <div class="field field-full">
        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="3" maxlength="500">{{ old('descripcion', $cobro->descripcion ?? '') }}</textarea>
        @error('descripcion') <span class="error-text">{{ $message }}</span> @enderror
    </div>
</div>
