<?php

// Proveedor de servicios propio de HydroPay.
use App\Providers\AppServiceProvider;

// Lista de proveedores que Laravel carga al arrancar (los paquetes como Sanctum se registran solos).
return [
    AppServiceProvider::class,
];
