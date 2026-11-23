{{-- Hereda el layout de visitantes. --}}
@extends('layouts.guest')

{{-- Título de la pestaña. --}}
@section('titulo', 'Iniciar sesión')

{{-- Contenido del formulario. --}}
@section('contenido')
<h1>Iniciar sesión</h1>

{{-- FORMULARIO DE ACCESO: POST /login. --}}
<form action="{{ route('login') }}" method="POST" novalidate>
    {{-- Token CSRF obligatorio. --}}
    @csrf

    {{-- Correo: old() conserva lo escrito si la validación falla. --}}
    <div class="field">
        <label for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus
               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        @error('email') <span class="error-text" id="email-error">{{ $message }}</span> @enderror
    </div>

    {{-- Contraseña (nunca se rellena con old() por seguridad). --}}
    <div class="field">
        <label for="password">Contraseña</label>
        <input id="password" type="password" name="password" autocomplete="current-password" required
               @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
        @error('password') <span class="error-text" id="password-error">{{ $message }}</span> @enderror
    </div>

    {{-- Opción "recordarme" (cookie remember_token). --}}
    <label class="checkbox-field">
        <input type="checkbox" name="recordar" value="1" @checked(old('recordar'))> Mantener sesión iniciada
    </label>

    {{-- Botón de envío. --}}
    <button type="submit" class="btn btn-primary full-width">Entrar</button>
</form>

{{-- Enlaces de ayuda. --}}
<p class="guest-links">
    <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a><br>
    <span>Las cuentas las crea el administrador del organismo.</span>
</p>
@endsection
