<?php

// Clase base de las migraciones.
use Illuminate\Database\Migrations\Migration;
// Definición de columnas.
use Illuminate\Database\Schema\Blueprint;
// Fachada de esquema.
use Illuminate\Support\Facades\Schema;

// Tablas del servicio Cache (CACHE_STORE=database). Aquí se guarda el resumen del panel.
return new class extends Migration
{
    // Crea las tablas.
    public function up(): void
    {
        // Valores guardados en caché.
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();           // Llave (ej. hydropay.dashboard.resumen).
            $table->mediumText('value');                // Valor serializado.
            $table->bigInteger('expiration')->index();  // Momento de expiración (timestamp).
        });

        // Bloqueos atómicos de caché.
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();           // Nombre del bloqueo.
            $table->string('owner');                    // Proceso dueño del bloqueo.
            $table->bigInteger('expiration')->index();  // Expiración del bloqueo.
        });
    }

    // Elimina las tablas.
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
