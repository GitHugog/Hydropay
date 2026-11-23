<?php

// Espacio de nombres de las políticas de autorización.
namespace App\Policies;

// Política del modelo Cliente. Laravel la asocia sola porque se llama "ModeloPolicy".
// Hereda todas las reglas de PoliticaBase (consultar: todos; registrar/editar: admin y cajero; eliminar: admin).
// La regla de negocio "no eliminar clientes con recibos" se revisa en el controlador.
class ClientePolicy extends PoliticaBase
{
    //
}
