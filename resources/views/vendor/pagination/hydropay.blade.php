{{-- VISTA DE PAGINACIÓN PROPIA (registrada en AppServiceProvider con Paginator::defaultView). --}}
{{-- Reemplaza la vista de Laravel basada en Tailwind, que este proyecto no usa. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación">
        <ul class="pagination">

            {{-- Botón "Anterior": deshabilitado en la primera página. --}}
            @if ($paginator->onFirstPage())
                <li class="disabled" aria-disabled="true"><span>‹ Anterior</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Anterior</a></li>
            @endif

            {{-- Números de página y separadores "...". --}}
            @foreach ($elements as $element)
                {{-- Separador de texto ("..."). --}}
                @if (is_string($element))
                    <li class="disabled" aria-disabled="true"><span>{{ $element }}</span></li>
                @endif

                {{-- Bloque de enlaces numerados. --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            {{-- Página actual. --}}
                            <li class="active" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            {{-- Otras páginas. --}}
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Botón "Siguiente": deshabilitado en la última página. --}}
            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente ›</a></li>
            @else
                <li class="disabled" aria-disabled="true"><span>Siguiente ›</span></li>
            @endif
        </ul>

        {{-- Resumen de registros mostrados. --}}
        <p class="pagination-info">
            Mostrando {{ $paginator->firstItem() }} - {{ $paginator->lastItem() }} de {{ $paginator->total() }} registros
        </p>
    </nav>
@endif
