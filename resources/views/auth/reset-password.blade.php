{{-- Hereda el layout de visitantes. --}}
@extends('layouts.guest')

{{-- Título de la pestaña. --}}
@section('titulo', 'Nueva contraseña')

{{-- Contenido. --}}
@section('contenido')
<h1>Crear nueva contraseña</h1>

{{-- FORMULARIO: POST /restablecer-contrasena. --}}
<form action="{{ route('password.update') }}" method="POST" novalidate>
    {{-- Token CSRF. --}}
    @csrf

    {{-- Token único de recuperación que llegó por correo. --}}
    <input type="hidden" name="token" value="{{ $token }}">

    {{-- Correo (viene prellenado desde el enlace). --}}
    <div class="field">
        <label for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" autocomplete="username" required>
        @error('email') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- Nueva contraseña con sus reglas visibles. --}}
    <div class="field">
        <label for="password">Nueva contraseña</label>
        <input id="password" type="password" name="password" autocomplete="new-password" required autofocus>
        <span class="hint">Mínimo 8 caracteres, con letras y números.</span>
        @error('password') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- Confirmación (regla "confirmed"). --}}
    <div class="field">
        <label for="password_confirmation">Confirmar contraseña</label>
        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
    </div>

    {{-- Botón de envío. --}}
    <button type="submit" class="btn btn-primary full-width">Guardar contraseña</button>
</form>
@endsection
