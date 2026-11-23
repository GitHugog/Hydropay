{{-- COMPONENTE BLADE: tarjeta de indicador para el panel y los reportes. --}}
{{-- Uso: <x-indicador etiqueta="Clientes" :valor="25" nota="Ver directorio →" :href="route('clientes.index')" /> --}}
@props(['etiqueta', 'valor', 'nota' => null, 'href' => null, 'claseValor' => ''])

{{-- Si recibe un enlace la tarjeta completa es clicable; si no, es un bloque simple. --}}
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'stat-card stat-card-enlace']) }}>
@else
    <div {{ $attributes->merge(['class' => 'stat-card']) }}>
@endif
        {{-- Nombre del indicador. --}}
        <span class="stat-label">{{ $etiqueta }}</span>
        {{-- Valor principal. --}}
        <strong class="stat-value {{ $claseValor }}">{{ $valor }}</strong>
        {{-- Nota opcional (por ejemplo, "Consultar →"). --}}
        @if ($nota)
            <span class="stat-note">{{ $nota }}</span>
        @endif
@if ($href)
    </a>
@else
    </div>
@endif
