{{-- COMPONENTE BLADE: <x-alerta tipo="success|error|info|warning">Texto</x-alerta> --}}
{{-- @props declara los atributos que recibe el componente y su valor por defecto. --}}
@props(['tipo' => 'info'])

@php
    // Las alertas de error se anuncian de inmediato a los lectores de pantalla (role="alert").
    $rol = $tipo === 'error' ? 'alert' : 'status';
@endphp

{{-- $attributes->merge combina la clase base con cualquier clase adicional enviada al componente. --}}
<div {{ $attributes->merge(['class' => 'alert alert-'.$tipo]) }} role="{{ $rol }}">
    {{-- Contenido escrito entre las etiquetas del componente. --}}
    {{ $slot }}
</div>
