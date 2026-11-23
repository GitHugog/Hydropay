{{-- Hereda el layout de visitantes. --}}
@extends('layouts.guest')

{{-- Título de la pestaña. --}}
@section('titulo', 'Recuperar contraseña')

{{-- Contenido. --}}
@section('contenido')
<h1>Recuperar contraseña</h1>
<p class="muted">Escribe tu correo y te enviaremos un enlace para crear una contraseña nueva.</p>

{{-- FORMULARIO: POST /olvide-mi-contrasena (máximo 5 envíos por minuto). --}}
<form action="{{ route('password.email') }}" method="POST" novalidate>
    {{-- Token CSRF. --}}
    @csrf

    {{-- Correo del usuario. --}}
    <div class="field">
        <label for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        @error('email') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    {{-- Botón de envío. --}}
    <button type="submit" class="btn btn-primary full-width">Enviar enlace</button>
</form>

{{-- Regreso al login. --}}
<p class="guest-links"><a href="{{ route('login') }}">Volver al inicio de sesión</a></p>
@endsection
