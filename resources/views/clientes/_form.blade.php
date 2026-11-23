{{-- FORMULARIO REUTILIZABLE de clientes (se incluye en create y edit con @include). --}}
{{-- $cliente existe solo al editar; old() recupera lo capturado si falla la validación. --}}

<div class="form-grid">

    {{-- Nombre completo. --}}
    <div class="field">
        <label for="nombre">Nombre completo *</label>
        <input id="nombre" type="text" name="nombre" maxlength="120" required
               value="{{ old('nombre', $cliente->nombre ?? '') }}"
               @error('nombre') aria-invalid="true" @enderror>
        @error('nombre') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- Número de cuenta (único). --}}
    <div class="field">
        <label for="numero_cuenta">Número de cuenta *</label>
        <input id="numero_cuenta" type="text" name="numero_cuenta" maxlength="30" required placeholder="Ej. HP-000123"
               value="{{ old('numero_cuenta', $cliente->numero_cuenta ?? '') }}"
               @error('numero_cuenta') aria-invalid="true" @enderror>
        @error('numero_cuenta') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- Dirección (ocupa las dos columnas). --}}
    <div class="field field-full">
        <label for="direccion">Dirección completa *</label>
        <input id="direccion" type="text" name="direccion" maxlength="255" required
               value="{{ old('direccion', $cliente->direccion ?? '') }}"
               @error('direccion') aria-invalid="true" @enderror>
        @error('direccion') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- Zona (municipio y localidad) del catálogo que administra el administrador. --}}
    <div class="field">
        <label for="zona_id">Zona (municipio y localidad)</label>
        <select id="zona_id" name="zona_id" @error('zona_id') aria-invalid="true" @enderror>
            <option value="">Sin zona</option>
            @foreach ($zonas ?? [] as $opcionZona)
                <option value="{{ $opcionZona->id }}" @selected((string) old('zona_id', $cliente->zona_id ?? '') === (string) $opcionZona->id)>
                    {{ $opcionZona->localidad }} — {{ $opcionZona->municipio }}
                </option>
            @endforeach
        </select>
        @if (($zonas ?? collect())->isEmpty())
            <span class="hint">No hay zonas registradas{{ auth()->user()->esAdmin() ? '; agrégalas en Administración › Zonas.' : '; pídele al administrador que las agregue.' }}</span>
        @endif
        @error('zona_id') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- Teléfono opcional. --}}
    <div class="field">
        <label for="telefono">Teléfono</label>
        <input id="telefono" type="tel" name="telefono" maxlength="20" placeholder="Ej. 33 1234 5678"
               value="{{ old('telefono', $cliente->telefono ?? '') }}"
               @error('telefono') aria-invalid="true" @enderror>
        @error('telefono') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- Correo opcional (para avisos de pago). --}}
    <div class="field">
        <label for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" maxlength="255"
               value="{{ old('email', $cliente->email ?? '') }}"
               @error('email') aria-invalid="true" @enderror>
        <span class="hint">Si lo capturas, el cliente recibirá la confirmación de cada pago.</span>
        @error('email') <span class="error-text">{{ $message }}</span> @enderror
    </div>
</div>

{{-- CUOTA DEL SERVICIO: solo el administrador la asigna (el cajero solo la consulta). --}}
@if (auth()->user()->esAdmin())
    <fieldset class="fieldset-cuota">
        <legend>Cuota del servicio</legend>
        <div class="form-grid">
            {{-- Cada cuánto se le cobra al cliente. --}}
            <div class="field">
                <label for="periodicidad">Periodicidad *</label>
                <select id="periodicidad" name="periodicidad" @error('periodicidad') aria-invalid="true" @enderror>
                    @foreach (\App\Models\Cliente::PERIODICIDADES as $opcion)
                        <option value="{{ $opcion }}" @selected(old('periodicidad', $cliente->periodicidad ?? 'mensual') === $opcion)>
                            {{ __('hydropay.periodicidades.'.$opcion) }}
                        </option>
                    @endforeach
                </select>
                @error('periodicidad') <span class="error-text">{{ $message }}</span> @enderror
            </div>

            {{-- Importe de cada recibo. --}}
            <div class="field">
                <label for="tarifa">Cuota por periodo ($)</label>
                <input id="tarifa" type="number" name="tarifa" min="0" step="0.01" placeholder="Ej. 150.00"
                       value="{{ old('tarifa', $cliente->tarifa ?? '') }}"
                       @error('tarifa') aria-invalid="true" @enderror>
                @error('tarifa') <span class="error-text">{{ $message }}</span> @enderror
            </div>
        </div>
        <p class="hint">
            Con cuota, HydroPay crea solo el recibo de cada periodo (mensual: "Noviembre 2026"; bimestral: "Noviembre-Diciembre 2026";
            anual: "Anual 2026"), con fecha límite al final del periodo. Al guardar se crea el del periodo actual.
            Si cambias la periodicidad a mitad de un periodo, revisa los recibos del cliente para no cobrar doble.
        </p>
    </fieldset>
@elseif (isset($cliente))
    {{-- El cajero ve la cuota asignada, pero no puede cambiarla. --}}
    <p class="hint">
        Cuota del servicio:
        <strong>{{ $cliente->tieneCuota() ? formato_moneda((float) $cliente->tarifa).' '.mb_strtolower(__('hydropay.periodicidades.'.$cliente->periodicidad)) : 'sin asignar' }}</strong>
        (solo el administrador puede modificarla).
    </p>
@endif

{{-- Estado del registro: marcado = activo. En un alta viene marcado por defecto. --}}
<label class="checkbox-field">
    <input type="checkbox" name="activo" value="1" @checked(old('activo', $cliente->activo ?? true))>
    Cliente activo (solo los clientes activos pueden recibir recibos nuevos)
</label>
