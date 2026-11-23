{{-- COMPONENTE BLADE: insignia de color según el estado del recibo. --}}
{{-- Uso: <x-estado-cobro :estado="$cobro->estado" /> --}}
@props(['estado'])

@php
    // Color de la insignia para cada estado (clases definidas en public/css/hydropay.css).
    $clases = [
        'pendiente' => 'badge-warning', // Amarillo: sin pagar.
        'pagado' => 'badge-success',    // Verde: liquidado.
        'vencido' => 'badge-danger',    // Rojo: atrasado.
        'cancelado' => 'badge-muted',   // Gris: anulado.
    ];
@endphp

{{-- El texto visible sale del helper etiqueta_estado() (lang/es/hydropay.php). --}}
<span {{ $attributes->merge(['class' => 'badge '.($clases[$estado] ?? 'badge-info')]) }}>{{ etiqueta_estado($estado) }}</span>
