{{-- PARCIAL: mensajes guardados en la sesión (flash) y resumen de errores de validación. --}}
{{-- Se incluye en el layout principal y en el de invitados. Usa el componente <x-alerta>. --}}

{{-- Confirmación de una operación exitosa: ->with('mensaje', ...). --}}
@if (session('mensaje'))
    <x-alerta tipo="success">{{ session('mensaje') }}</x-alerta>
@endif

{{-- Error o advertencia de negocio: ->with('error', ...). --}}
@if (session('error'))
    <x-alerta tipo="error">{{ session('error') }}</x-alerta>
@endif

{{-- Mensaje informativo: ->with('status', ...). --}}
@if (session('status'))
    <x-alerta tipo="info">{{ session('status') }}</x-alerta>
@endif

{{-- Si una validación falla se listan todos los errores; cada campo también muestra el suyo. --}}
@if ($errors->any())
    <x-alerta tipo="error">
        <strong>Revisa la información capturada:</strong>
        <ul class="lista-errores">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alerta>
@endif
