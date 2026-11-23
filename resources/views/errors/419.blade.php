{{-- PÁGINA DE ERROR 419: Laravel la muestra automáticamente cuando ocurre este código HTTP. --}}
@extends('layouts.error')

{{-- Código HTTP. --}}
@section('codigo', '419')

{{-- Título del error. --}}
@section('titulo', 'La sesión expiró')

{{-- Explicación para el usuario. --}}
@section('mensaje', 'Por seguridad la página caducó. Vuelve a cargarla e intenta de nuevo; tus datos no se guardaron.')
