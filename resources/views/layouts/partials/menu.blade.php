{{-- PARCIAL: pestañas de navegación entre módulos (se incluye en layouts/app.blade.php). --}}
{{-- En celulares la fila se desliza de lado; no hay menú oculto que abrir. --}}
@php
    // Pestañas de operación diaria: [texto, ruta, patrón de rutas que la marcan como activa].
    $pestanas = [
        ['Inicio', 'dashboard', 'dashboard'],
        ['Clientes', 'clientes.index', 'clientes.*'],
        ['Cobros', 'cobros.index', 'cobros.*'],
        ['Pagos', 'pagos.index', 'pagos.*'],
        ['Reportes', 'reportes.index', 'reportes.*'],
    ];

    // Pestañas de administración: solo el administrador (Gate "gestionar-usuarios" en AppServiceProvider).
    $pestanasAdmin = [
        ['Zonas', 'zonas.index', 'zonas.*'],
        ['Usuarios', 'usuarios.index', 'usuarios.*'],
    ];
@endphp

<nav class="pestanas" aria-label="Secciones del sistema">
    <div class="contenedor pestanas-fila">
        {{-- request()->routeIs() marca la pestaña del módulo abierto. --}}
        @foreach ($pestanas as [$texto, $ruta, $patron])
            <a href="{{ route($ruta) }}" class="pestana {{ request()->routeIs($patron) ? 'activa' : '' }}"
               @if (request()->routeIs($patron)) aria-current="page" @endif>{{ $texto }}</a>
        @endforeach

        @can('gestionar-usuarios')
            {{-- Separador visual entre la operación diaria y la administración. --}}
            <span class="pestanas-separador" aria-hidden="true"></span>
            @foreach ($pestanasAdmin as [$texto, $ruta, $patron])
                <a href="{{ route($ruta) }}" class="pestana {{ request()->routeIs($patron) ? 'activa' : '' }}"
                   @if (request()->routeIs($patron)) aria-current="page" @endif>{{ $texto }}</a>
            @endforeach
        @endcan
    </div>
</nav>
