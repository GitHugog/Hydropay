{{-- PÁGINA DE ERROR 429: Laravel la muestra automáticamente cuando ocurre este código HTTP. --}}
@extends('layouts.error')

{{-- Código HTTP. --}}
@section('codigo', '429')

{{-- Título del error. --}}
@section('titulo', 'Demasiadas solicitudes')

{{-- Explicación para el usuario. --}}
@section('mensaje', 'Hiciste demasiados intentos en poco tiempo. Espera un minuto e intenta otra vez.')
