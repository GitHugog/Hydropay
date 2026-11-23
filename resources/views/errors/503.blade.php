{{-- PÁGINA DE ERROR 503: Laravel la muestra automáticamente cuando ocurre este código HTTP. --}}
@extends('layouts.error')

{{-- Código HTTP. --}}
@section('codigo', '503')

{{-- Título del error. --}}
@section('titulo', 'En mantenimiento')

{{-- Explicación para el usuario. --}}
@section('mensaje', 'HydroPay está en mantenimiento. Vuelve a intentarlo en unos minutos.')
