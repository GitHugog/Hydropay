<?php

// Clase base de las migraciones.
use Illuminate\Database\Migrations\Migration;
// Definición de columnas.
use Illuminate\Database\Schema\Blueprint;
// Fachada para modificar el esquema.
use Illuminate\Support\Facades\Schema;

// MIGRACIÓN: catálogo de zonas (municipio y localidad) y zona de cada cliente.
// Permite organizar el padrón por pueblos o rancherías y filtrar los clientes que atiende cada cajero.
return new class extends Migration
{
    // Aplica el cambio: php artisan migrate
    public function up(): void
    {
        // Tabla de zonas.
        Schema::create('zonas', function (Blueprint $table) {
            // Llave primaria autoincremental.
            $table->id();
            // Municipio al que pertenece la localidad (por ejemplo, "Tala").
            $table->string('municipio', 120);
            // Pueblo, colonia o ranchería (por ejemplo, "San Isidro Mazatepec").
            $table->string('localidad', 120);
            // created_at y updated_at.
            $table->timestamps();
            // No se repite la misma localidad dentro del mismo municipio.
            $table->unique(['municipio', 'localidad']);
        });

        // Zona de cada cliente (opcional).
        Schema::table('clientes', function (Blueprint $table) {
            // SQLite no permite agregar una llave foránea a una tabla existente; la integridad la cuidan
            // la validación (exists:zonas,id) y ZonaController, que no elimina zonas con clientes.
            $table->unsignedBigInteger('zona_id')->nullable()->after('direccion')->index();
        });
    }

    // Revierte el cambio: php artisan migrate:rollback
    public function down(): void
    {
        // Quita la columna de clientes (primero el índice).
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['zona_id']);
            $table->dropColumn('zona_id');
        });

        // Elimina el catálogo.
        Schema::dropIfExists('zonas');
    }
};
