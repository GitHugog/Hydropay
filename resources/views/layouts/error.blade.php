<!DOCTYPE html>
{{-- LAYOUT DE PÁGINAS DE ERROR: no depende de la sesión, así funciona aunque el usuario no esté autenticado. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    {{-- Codificación de caracteres. --}}
    <meta charset="UTF-8">
    {{-- Diseño responsivo. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Título con el código de error. --}}
    <title>Error @yield('codigo'): @yield('titulo') | {{ config('app.name') }}</title>
    {{-- Ícono. --}}
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    {{-- Tipografías y estilos del sistema. --}}
    @include('layouts.partials.fuentes')
    <link rel="stylesheet" href="{{ asset('css/hydropay.css') }}">
</head>

<body class="pagina-error">
    {{-- Membrete simple (no depende de la sesión). --}}
    <header class="membrete">
        <div class="contenedor membrete-fila">
            <a class="marca" href="{{ url('/') }}">
                @include('layouts.partials.marca')
            </a>
        </div>
    </header>

    {{-- Contenido del error. --}}
    <main class="contenedor error-cuerpo" id="contenido">
        {{-- Código HTTP. --}}
        <p class="error-codigo">Error @yield('codigo')</p>
        {{-- Título del error. --}}
        <h1 class="error-titulo">@yield('titulo')</h1>
        {{-- Explicación para el usuario. --}}
        <p class="error-mensaje">@yield('mensaje')</p>

        {{-- Acción sugerida: volver al inicio si hay sesión o ir al login si no la hay. --}}
        <div class="actions">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">Ir al inicio</a>
                <a href="{{ url()->previous() }}" class="btn btn-light">Regresar</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary">Ir al inicio de sesión</a>
            @endauth
        </div>
    </main>
</body>
</html>
