<?php

// Clase base de las migraciones.
use Illuminate\Database\Migrations\Migration;
// Definición de columnas.
use Illuminate\Database\Schema\Blueprint;
// Fachada de esquema.
use Illuminate\Support\Facades\Schema;

// Tabla de tokens de la API (paquete Laravel Sanctum).
// Se incluye en el proyecto para no depender de "php artisan install:api".
// Es condicional: si la tabla ya existe (por ejemplo, porque se publicó la migración de Sanctum), no hace nada.
return new class extends Migration
{
    // Crea la tabla.
    public function up(): void
    {
        // Si ya existe se omite para no duplicarla.
        if (Schema::hasTable('personal_access_tokens')) {
            return;
        }

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();                                         // Identificador del token.
            $table->morphs('tokenable');                          // Dueño del token (tokenable_type + tokenable_id = App\Models\User, id).
            $table->text('name');                                 // Nombre del dispositivo (ej. "Postman").
            $table->string('token', 64)->unique();                // Hash SHA-256 del token (el texto plano nunca se guarda).
            $table->text('abilities')->nullable();                // Habilidades: ["leer","escribir","eliminar"].
            $table->timestamp('last_used_at')->nullable();        // Último uso.
            $table->timestamp('expires_at')->nullable()->index(); // Vencimiento.
            $table->timestamps();                                 // Fechas de creación y modificación.
        });
    }

    // Elimina la tabla al revertir.
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
