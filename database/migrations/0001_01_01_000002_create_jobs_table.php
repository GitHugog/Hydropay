<?php

// Clase base de las migraciones.
use Illuminate\Database\Migrations\Migration;
// Definición de columnas.
use Illuminate\Database\Schema\Blueprint;
// Fachada de esquema.
use Illuminate\Support\Facades\Schema;

// Tablas de colas de trabajo (QUEUE_CONNECTION=database). Quedan listas para enviar correos en segundo plano.
return new class extends Migration
{
    // Crea las tablas.
    public function up(): void
    {
        // Trabajos pendientes.
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();                                       // Llave primaria.
            $table->string('queue')->index();                   // Nombre de la cola.
            $table->longText('payload');                        // Trabajo serializado.
            $table->unsignedSmallInteger('attempts');           // Intentos realizados.
            $table->unsignedInteger('reserved_at')->nullable(); // Momento en que un proceso lo tomó.
            $table->unsignedInteger('available_at');            // Momento desde el que puede ejecutarse.
            $table->unsignedInteger('created_at');              // Momento de creación.
        });

        // Lotes de trabajos.
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();                    // Identificador del lote.
            $table->string('name');                             // Nombre del lote.
            $table->integer('total_jobs');                      // Total de trabajos.
            $table->integer('pending_jobs');                    // Pendientes.
            $table->integer('failed_jobs');                     // Fallidos.
            $table->longText('failed_job_ids');                 // IDs de los fallidos.
            $table->mediumText('options')->nullable();          // Opciones del lote.
            $table->integer('cancelled_at')->nullable();        // Momento de cancelación.
            $table->integer('created_at');                      // Momento de creación.
            $table->integer('finished_at')->nullable();         // Momento de término.
        });

        // Trabajos que fallaron definitivamente.
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();                                       // Llave primaria.
            $table->string('uuid')->unique();                   // Identificador único.
            $table->string('connection');                       // Conexión de cola.
            $table->string('queue');                            // Cola.
            $table->longText('payload');                        // Trabajo serializado.
            $table->longText('exception');                      // Error ocurrido.
            $table->timestamp('failed_at')->useCurrent();       // Momento del fallo.
            $table->index(['connection', 'queue', 'failed_at']); // Índice de búsqueda.
        });
    }

    // Elimina las tablas.
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
