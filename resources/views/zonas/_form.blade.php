{{-- FORMULARIO REUTILIZABLE de zonas (alta y edición, solo administrador). --}}
<div class="form-grid">
    {{-- Municipio. --}}
    <div class="field">
        <label for="municipio">Municipio *</label>
        <input id="municipio" type="text" name="municipio" maxlength="120" required placeholder="Ej. Tala"
               value="{{ old('municipio', $zona->municipio ?? '') }}" @error('municipio') aria-invalid="true" @enderror>
        @error('municipio') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- Localidad: pueblo, colonia o ranchería. --}}
    <div class="field">
        <label for="localidad">Localidad (pueblo, colonia o ranchería) *</label>
        <input id="localidad" type="text" name="localidad" maxlength="120" required placeholder="Ej. San Isidro Mazatepec"
               value="{{ old('localidad', $zona->localidad ?? '') }}" @error('localidad') aria-invalid="true" @enderror>
        @error('localidad') <span class="error-text">{{ $message }}</span> @enderror
    </div>
</div>
