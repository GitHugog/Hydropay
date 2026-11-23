<?php

// Espacio de nombres de los controladores web.
namespace App\Http\Controllers;

// Controlador base: todos los controladores de HydroPay heredan de esta clase.
// La validación y limpieza de datos se hace en los Form Requests (app/Http/Requests)
// y los permisos en las Policies (app/Policies), por eso esta clase queda vacía.
abstract class Controller
{
    //
}
