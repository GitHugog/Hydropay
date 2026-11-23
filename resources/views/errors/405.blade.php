{{-- PÁGINA DE ERROR 405: Laravel la muestra automáticamente cuando ocurre este código HTTP. --}}
@extends('layouts.error')

{{-- Código HTTP. --}}
@section('codigo', '405')

{{-- Título del error. --}}
@section('titulo', 'Método no permitido')

{{-- Explicación para el usuario. --}}
@section('mensaje', 'Esta acción no está disponible desde esta dirección.')
