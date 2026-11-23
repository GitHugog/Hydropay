{{-- PARCIAL: marca de HydroPay (gota dibujada en SVG + nombre + organismo). --}}
{{-- Se usa en el membrete del sistema y en las pantallas de acceso y de error. --}}
<span class="marca-gota" aria-hidden="true">
    <svg viewBox="0 0 24 24" width="22" height="22" focusable="false">
        <path d="M12 2.5c-.3 0-.5.1-.7.4C9.5 5.4 5 11.2 5 15a7 7 0 0 0 14 0c0-3.8-4.5-9.6-6.3-12.1-.2-.3-.4-.4-.7-.4Z" fill="currentColor"/>
        <path d="M9.2 15.4a2.9 2.9 0 0 0 2.6 2.7" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" opacity=".85"/>
    </svg>
</span>
<span class="marca-texto">
    <strong>{{ config('app.name') }}</strong>
    <small>{{ config('hydropay.organismo') }}</small>
</span>
