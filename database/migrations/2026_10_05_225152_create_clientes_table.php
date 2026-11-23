<?php

// Clase base de las migraciones.
use Illuminate\Database\Migrations\Migration;
// Definición de columnas.
use Illuminate\Database\Schema\Blueprint;
// Fachada de esquema.
use Illuminate\Support\Facades\Schema;

// Tabla "clientes": titulares de una cuenta del servicio de agua.
return new class extends Migration
{
    // Crea la tabla.
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();                                    // Identificador único del cliente.
            $table->string('nombre', 120);                   // Nombre completo.
            $table->string('numero_cuenta', 30)->unique();   // Cuenta única del servicio.
            $table->string('direccion');                     // Domicilio del servicio.
            $table->string('telefono', 20)->nullable();      // Teléfono opcional.
            $table->string('email')->nullable();             // Correo opcional (avisos de pago).
            $table->boolean('activo')->default(true);        // Registro habilitado o no.
            $table->timestamps();                            // Fechas de creación y modificación.
        });
    }

    // Elimina la tabla al revertir la migración.
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
