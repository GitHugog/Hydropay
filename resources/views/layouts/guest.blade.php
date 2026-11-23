<!DOCTYPE html>
{{-- LAYOUT PARA VISITANTES (sin sesión): login y recuperación de contraseña. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    {{-- Codificación de caracteres. --}}
    <meta charset="UTF-8">
    {{-- Diseño responsivo. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Color de la barra del navegador en celulares. --}}
    <meta name="theme-color" content="#0F4C81">
    {{-- Título de la pestaña. --}}
    <title>@yield('titulo', 'Acceso') | {{ config('app.name') }}</title>
    {{-- Ícono. --}}
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    {{-- Tipografías y estilos del sistema. --}}
    @include('layouts.partials.fuentes')
    <link rel="stylesheet" href="{{ asset('css/hydropay.css') }}">
</head>

<body class="acceso">
    {{-- Franja del organismo: marca y para qué sirve el sistema. --}}
    <header class="acceso-franja">
        <div class="marca">
            @include('layouts.partials.marca')
        </div>
        <p class="acceso-lema">Cobro del servicio de agua potable en la cabecera y en las localidades del municipio.</p>
    </header>

    {{-- Formulario (login, recuperar o restablecer contraseña). --}}
    <main class="acceso-formulario" id="contenido">
        <div class="acceso-caja">
            {{-- Mensajes de sesión y errores (mismo parcial que el layout principal). --}}
            @include('layouts.partials.mensajes')

            @yield('contenido')
        </div>
    </main>
</body>
</html>
