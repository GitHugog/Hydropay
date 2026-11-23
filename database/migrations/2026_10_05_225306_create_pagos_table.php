<?php

// Clase base de las migraciones.
use Illuminate\Database\Migrations\Migration;
// Definición de columnas.
use Illuminate\Database\Schema\Blueprint;
// Fachada de esquema.
use Illuminate\Support\Facades\Schema;

// Tabla "pagos": liquidación completa de un recibo.
return new class extends Migration
{
    // Crea la tabla.
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();                                        // Identificador (folio) del pago.
            $table->foreignId('cobro_id')                        // Llave foránea al recibo.
                ->constrained('cobros')                          // Referencia a cobros.id.
                ->restrictOnDelete();                            // No permite borrar un recibo con pago.
            $table->foreignId('user_id')                         // Usuario que registró el pago (auditoría).
                ->nullable()                                     // Puede quedar vacío...
                ->constrained('users')                           // ...referencia a users.id...
                ->nullOnDelete();                                // ...y se pone null si se elimina ese usuario.
            $table->decimal('monto', 10, 2);                     // Importe recibido (igual al del recibo).
            $table->string('metodo', 40);                        // efectivo, transferencia, tarjeta o deposito.
            $table->string('referencia')->nullable();           // Folio o referencia bancaria.
            $table->date('fecha_pago');                          // Fecha en que se recibió el pago.
            $table->string('comprobante')->nullable();           // Ruta privada del comprobante (File Storage).
            $table->timestamps();                                // Fechas de creación y modificación.
        });
    }

    // Elimina la tabla al revertir la migración.
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
