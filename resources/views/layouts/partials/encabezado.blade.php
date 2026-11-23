{{-- PARCIAL: ruta de navegación sobre el título de cada pantalla (se incluye en layouts/app.blade.php). --}}
@php
    // Nombre de la ruta actual, por ejemplo "clientes.edit".
    $rutaActual = request()->route()?->getName() ?? '';

    // Se separa en módulo ("clientes") y acción ("edit").
    [$modulo, $accion] = array_pad(explode('.', $rutaActual, 2), 2, null);

    // Nombre visible y ruta del listado de cada módulo (para la ruta de navegación o "migas").
    $modulos = [
        'clientes' => ['Clientes', 'clientes.index'],
        'cobros' => ['Cobros', 'cobros.index'],
        'pagos' => ['Pagos', 'pagos.index'],
        'reportes' => ['Reportes', 'reportes.index'],
        'usuarios' => ['Usuarios', 'usuarios.index'],
        'zonas' => ['Zonas', 'zonas.index'],
        'cuenta' => ['Mi cuenta', 'cuenta.password.edit'],
    ];

    // Nombre visible de cada acción.
    $acciones = [
        'create' => 'Registrar',
        'edit' => 'Editar',
        'show' => 'Detalle',
        'password.edit' => 'Cambiar contraseña',
    ];
@endphp

{{-- Ruta de navegación: Inicio › Módulo › Acción. En el inicio no se muestra (ya estás ahí). --}}
@isset($modulos[$modulo])
    <nav class="migas" aria-label="Ruta de navegación">
        <a href="{{ route('dashboard') }}">Inicio</a>
        <span aria-hidden="true">›</span>
        @isset($acciones[$accion])
            <a href="{{ route($modulos[$modulo][1]) }}">{{ $modulos[$modulo][0] }}</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page">{{ $acciones[$accion] }}</span>
        @else
            <span aria-current="page">{{ $modulos[$modulo][0] }}</span>
        @endisset
    </nav>
@endisset
