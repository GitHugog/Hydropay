{{-- Hereda la plantilla principal. --}}
@extends('layouts.app')

{{-- Título. --}}
@section('titulo', 'Cambiar contraseña')

{{-- Contenido. --}}
@section('contenido')

{{-- Encabezado. --}}
<div class="page-heading">
    <div>
        <h1>Cambiar contraseña</h1>
        <p class="muted">Escribe tu contraseña actual y después la nueva.</p>
    </div>
</div>

<section class="panel">
    {{-- FORMULARIO: PUT /cuenta/contrasena → CuentaController@actualizarPassword. --}}
    <form action="{{ route('cuenta.password.update') }}" method="POST" novalidate>
        {{-- Token CSRF. --}}
        @csrf
        {{-- Método PUT simulado. --}}
        @method('PUT')

        <div class="form-grid">
            {{-- Contraseña actual (se compara con el hash guardado). --}}
            <div class="field field-full">
                <label for="password_actual">Contraseña actual</label>
                <input id="password_actual" type="password" name="password_actual" autocomplete="current-password" required>
                @error('password_actual') <span class="error-text">{{ $message }}</span> @enderror
            </div>

            {{-- Nueva contraseña. --}}
            <div class="field">
                <label for="password">Nueva contraseña</label>
                <input id="password" type="password" name="password" autocomplete="new-password" required>
                <span class="hint">Mínimo 8 caracteres, con letras y números, distinta de la actual.</span>
                @error('password') <span class="error-text">{{ $message }}</span> @enderror
            </div>

            {{-- Confirmación. --}}
            <div class="field">
                <label for="password_confirmation">Confirmar nueva contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
            </div>
        </div>

        {{-- Botones. --}}
        <div class="form-actions">
            <a href="{{ route('dashboard') }}" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
        </div>
    </form>
</section>

@endsection
