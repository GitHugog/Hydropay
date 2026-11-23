{{-- FORMULARIO REUTILIZABLE de usuarios (solo administrador). --}}
@php
    // true al editar un usuario existente.
    $esEdicion = isset($usuario);
@endphp

<div class="form-grid">
    {{-- Nombre. --}}
    <div class="field">
        <label for="name">Nombre *</label>
        <input id="name" type="text" name="name" maxlength="120" required
               value="{{ old('name', $usuario->name ?? '') }}" @error('name') aria-invalid="true" @enderror>
        @error('name') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- Correo de acceso (único). --}}
    <div class="field">
        <label for="email">Correo electrónico *</label>
        <input id="email" type="email" name="email" maxlength="255" required autocomplete="off"
               value="{{ old('email', $usuario->email ?? '') }}" @error('email') aria-invalid="true" @enderror>
        @error('email') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- Rol. Al editarse a sí mismo el administrador no puede cambiarlo. --}}
    <div class="field">
        <label for="rol">Rol *</label>
        <select id="rol" name="rol" required @disabled($esEdicion && $usuario->is(auth()->user()))>
            @foreach (\App\Models\User::ROLES as $rol)
                <option value="{{ $rol }}" @selected(old('rol', $usuario->rol ?? 'consulta') === $rol)>{{ __('hydropay.roles.'.$rol) }}</option>
            @endforeach
        </select>
        {{-- Un select deshabilitado no se envía: se manda el rol actual en un campo oculto. --}}
        @if ($esEdicion && $usuario->is(auth()->user()))
            <input type="hidden" name="rol" value="{{ $usuario->rol }}">
            <span class="hint">No puedes cambiar tu propio rol.</span>
        @else
            <span class="hint">Administrador: todo. Cajero: registra y cobra. Consulta: solo ve la información.</span>
        @endif
        @error('rol') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- Espacio vacío para alinear la cuadrícula. --}}
    <div class="field" aria-hidden="true"></div>

    {{-- Contraseña: obligatoria al crear, opcional al editar. --}}
    <div class="field">
        <label for="password">Contraseña {{ $esEdicion ? '(déjala vacía para no cambiarla)' : '*' }}</label>
        <input id="password" type="password" name="password" autocomplete="new-password" @required(! $esEdicion)>
        <span class="hint">Mínimo 8 caracteres, con letras y números.</span>
        @error('password') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- Confirmación. --}}
    <div class="field">
        <label for="password_confirmation">Confirmar contraseña</label>
        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" @required(! $esEdicion)>
    </div>
</div>
