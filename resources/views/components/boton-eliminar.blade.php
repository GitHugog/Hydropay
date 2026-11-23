{{-- COMPONENTE BLADE: formulario de eliminación con confirmación, CSRF y método DELETE. --}}
{{-- Uso: <x-boton-eliminar :action="route('clientes.destroy', $cliente)" pregunta="¿Eliminar este cliente?" /> --}}
@props(['action', 'pregunta' => '¿Deseas eliminar este registro?', 'texto' => 'Eliminar'])

{{-- data-confirmar: hydropay.js pide confirmación antes de enviar. --}}
<form action="{{ $action }}" method="POST" data-confirmar="{{ $pregunta }}" class="form-en-linea">
    {{-- Token CSRF obligatorio. --}}
    @csrf
    {{-- HTML solo envía GET/POST; este campo indica a Laravel que es DELETE. --}}
    @method('DELETE')
    {{-- Botón de envío. --}}
    <button type="submit" {{ $attributes->merge(['class' => 'btn btn-danger btn-small']) }}>{{ $texto }}</button>
</form>
