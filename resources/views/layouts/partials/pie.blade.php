{{-- PARCIAL: pie de página compartido. --}}
<footer class="pie">
    <div class="contenedor pie-fila">
        {{-- Organismo configurado en .env (HYDROPAY_ORGANISMO). --}}
        <span>{{ config('hydropay.organismo') }}</span>
        {{-- Nombre del sistema, versión y año actual. --}}
        <span>{{ config('app.name') }} versión 1.0.0, {{ date('Y') }}</span>
    </div>
</footer>
