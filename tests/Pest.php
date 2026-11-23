<?php

// Clase base de las pruebas con la aplicación Laravel cargada.
use Tests\TestCase;
// Rasgo que reconstruye la base de datos en memoria para cada prueba.
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN DE PEST
|--------------------------------------------------------------------------
| Todas las pruebas de tests/Feature:
|   - extienden Tests\TestCase (aplicación Laravel completa);
|   - usan RefreshDatabase (SQLite en memoria, definida en phpunit.xml),
|     así cada prueba inicia con la base vacía y no toca la base real.
| Las pruebas de tests/Unit son funciones puras y no necesitan base de datos.
*/
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
