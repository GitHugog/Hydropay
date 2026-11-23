{{-- PÁGINA DE ERROR 500: Laravel la muestra automáticamente cuando ocurre este código HTTP. --}}
@extends('layouts.error')

{{-- Código HTTP. --}}
@section('codigo', '500')

{{-- Título del error. --}}
@section('titulo', 'Error interno')

{{-- Explicación para el usuario. --}}
@section('mensaje', 'Ocurrió un problema inesperado. El error quedó registrado; intenta de nuevo en unos minutos.')
