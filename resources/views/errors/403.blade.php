{{-- PÁGINA DE ERROR 403: Laravel la muestra automáticamente cuando ocurre este código HTTP. --}}
@extends('layouts.error')

{{-- Código HTTP. --}}
@section('codigo', '403')

{{-- Título del error. --}}
@section('titulo', 'Acceso denegado')

{{-- Si el 403 trae un motivo propio (Policy o middleware VerificarRol) se muestra; si no, uno general. --}}
@section('mensaje')
    {{ $exception->getMessage() && $exception->getMessage() !== 'This action is unauthorized.' ? $exception->getMessage() : 'No tienes permiso para realizar esta acción.' }}
    @auth
        <br><small class="hint">Tu rol actual es: {{ __('hydropay.roles.'.auth()->user()->rol) }}.</small>
    @endauth
@endsection
