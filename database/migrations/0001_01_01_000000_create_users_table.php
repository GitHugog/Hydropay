<?php

// Clase base de las migraciones.
use Illuminate\Database\Migrations\Migration;
// Definición de columnas de una tabla.
use Illuminate\Database\Schema\Blueprint;
// Fachada para crear y eliminar tablas.
use Illuminate\Support\Facades\Schema;

// Tablas de usuarios, tokens de recuperación de contraseña y sesiones.
return new class extends Migration
{
    // Se ejecuta con "php artisan migrate".
    public function up(): void
    {
        // Personal que inicia sesión en HydroPay.
        Schema::create('users', function (Blueprint $table) {
            $table->id();                                         // Llave primaria autoincremental.
            $table->string('name');                               // Nombre visible.
            $table->string('email')->unique();                    // Correo de acceso (único).
            $table->timestamp('email_verified_at')->nullable();   // Fecha de verificación del correo.
            $table->string('password');                           // Contraseña cifrada con hash (nunca texto plano).
            $table->string('rol', 20)->default('consulta')->index(); // Rol: admin, cajero o consulta.
            $table->rememberToken();                              // Token de "mantener sesión iniciada".
            $table->timestamps();                                 // created_at y updated_at.
        });

        // Tokens temporales para la recuperación de contraseña.
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();                   // Correo que pidió el enlace.
            $table->string('token');                              // Token cifrado.
            $table->timestamp('created_at')->nullable();          // Momento de creación (para la expiración).
        });

        // Sesiones activas (SESSION_DRIVER=database).
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();                      // Identificador de la sesión.
            $table->foreignId('user_id')->nullable()->index();    // Usuario de la sesión (null si es visitante).
            $table->string('ip_address', 45)->nullable();         // IP del navegador.
            $table->text('user_agent')->nullable();               // Navegador utilizado.
            $table->longText('payload');                          // Datos de la sesión.
            $table->integer('last_activity')->index();            // Última actividad (para expirar sesiones).
        });
    }

    // Se ejecuta con "php artisan migrate:rollback".
    public function down(): void
    {
        // Elimina las tablas en orden.
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
