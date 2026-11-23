{{-- PÁGINA DE ERROR 404: Laravel la muestra automáticamente cuando ocurre este código HTTP. --}}
@extends('layouts.error')

{{-- Código HTTP. --}}
@section('codigo', '404')

{{-- Título del error. --}}
@section('titulo', 'Página no encontrada')

{{-- Explicación para el usuario. --}}
@section('mensaje', 'La dirección que buscas no existe o el registro fue eliminado.')
