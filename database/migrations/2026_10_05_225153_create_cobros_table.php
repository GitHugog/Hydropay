<?php

// Clase base de las migraciones.
use Illuminate\Database\Migrations\Migration;
// Definición de columnas.
use Illuminate\Database\Schema\Blueprint;
// Fachada de esquema.
use Illuminate\Support\Facades\Schema;

// Tabla "cobros": recibos o adeudos de cada cliente.
return new class extends Migration
{
    // Crea la tabla.
    public function up(): void
    {
        Schema::create('cobros', function (Blueprint $table) {
            $table->id();                                        // Identificador único del recibo.
            $table->foreignId('cliente_id')                      // Llave foránea al cliente.
                ->constrained('clientes')                        // Referencia a clientes.id.
                ->restrictOnDelete();                            // No permite borrar un cliente con recibos.
            $table->string('periodo', 30);                       // Periodo o concepto (ej. "Noviembre 2026").
            $table->decimal('monto', 10, 2);                     // Importe total.
            $table->date('fecha_vencimiento')->nullable();       // Fecha límite de pago.
            $table->string('estado', 20)->default('pendiente')->index(); // pendiente, pagado, vencido o cancelado.
            $table->text('descripcion')->nullable();             // Observaciones opcionales.
            $table->timestamps();                                // Fechas de creación y modificación.
        });
    }

    // Elimina la tabla al revertir la migración.
    public function down(): void
    {
        Schema::dropIfExists('cobros');
    }
};
