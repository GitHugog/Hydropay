<!DOCTYPE html>
{{-- Idioma del documento según APP_LOCALE (es). --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    {{-- Codificación de caracteres (acentos y ñ). --}}
    <meta charset="UTF-8">
    {{-- Ajusta el ancho al dispositivo: base del diseño responsivo. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Color de la barra del navegador en celulares (azul agua del membrete). --}}
    <meta name="theme-color" content="#0F4C81">
    {{-- Título dinámico: cada vista define @section('titulo'). --}}
    <title>@yield('titulo', 'Inicio') | {{ config('app.name') }}</title>
    {{-- Ícono de la pestaña (gota de agua en SVG). --}}
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    {{-- Tipografías (parcial). --}}
    @include('layouts.partials.fuentes')
    {{-- Hoja de estilos única del sistema (public/css/hydropay.css). --}}
    <link rel="stylesheet" href="{{ asset('css/hydropay.css') }}">
</head>

<body>
    {{-- Enlace de accesibilidad: permite saltar la navegación con el teclado. --}}
    <a href="#contenido" class="skip-link">Saltar al contenido</a>

    {{-- MEMBRETE: marca, organismo y sesión del usuario (como el encabezado de un recibo oficial). --}}
    <header class="membrete">
        <div class="contenedor membrete-fila">
            {{-- Marca: lleva al inicio. --}}
            <a class="marca" href="{{ route('dashboard') }}">
                @include('layouts.partials.marca')
            </a>

            {{-- Usuario conectado, cambio de contraseña y cierre de sesión. --}}
            <div class="sesion">
                <span class="sesion-usuario">
                    {{ auth()->user()?->name }}
                    <small>{{ __('hydropay.roles.'.auth()->user()?->rol) }}</small>
                </span>
                <a href="{{ route('cuenta.password.edit') }}" class="sesion-enlace">Mi contraseña</a>
                {{-- El cierre de sesión es POST y lleva token CSRF (no se puede forzar con un enlace). --}}
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="sesion-salir">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </header>

    {{-- PESTAÑAS de navegación entre módulos (parcial reutilizable). --}}
    @include('layouts.partials.menu')

    {{-- Contenido principal de cada pantalla. --}}
    <main id="contenido" tabindex="-1">
        <div class="contenedor">
            {{-- Ruta de navegación: Inicio › Módulo › Acción (parcial). --}}
            @include('layouts.partials.encabezado')

            {{-- MENSAJES de sesión y resumen de errores de validación (parcial). --}}
            @include('layouts.partials.mensajes')

            {{-- Aquí se inserta el contenido de cada vista (@section('contenido')). --}}
            @yield('contenido')
        </div>
    </main>

    {{-- PIE DE PÁGINA compartido (parcial). --}}
    @include('layouts.partials.pie')

    {{-- JavaScript mínimo: confirmaciones, importe del pago e impresión (public/js/hydropay.js). --}}
    <script src="{{ asset('js/hydropay.js') }}" defer></script>
</body>
</html>
