<?php

// Clase base de las migraciones.
use Illuminate\Database\Migrations\Migration;
// Definición de columnas.
use Illuminate\Database\Schema\Blueprint;
// Fachada para modificar el esquema.
use Illuminate\Support\Facades\Schema;

// MIGRACIÓN: agrega a cada cliente su cuota del servicio.
// - periodicidad: cada cuánto se le cobra (mensual, bimestral o anual).
// - tarifa: importe de cada recibo; si está vacía no se generan recibos automáticos.
return new class extends Migration
{
    // Aplica el cambio: php artisan migrate
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Periodicidad del cobro; los clientes existentes quedan como mensuales.
            $table->string('periodicidad', 10)->default('mensual')->after('email');
            // Importe por periodo (hasta 999,999.99); null = sin cuota asignada todavía.
            $table->decimal('tarifa', 8, 2)->nullable()->after('periodicidad');
        });
    }

    // Revierte el cambio: php artisan migrate:rollback
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Elimina las dos columnas agregadas.
            $table->dropColumn(['periodicidad', 'tarifa']);
        });
    }
};
