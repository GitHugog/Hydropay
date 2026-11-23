<?php

// Clase base de las migraciones.
use Illuminate\Database\Migrations\Migration;
// Definición de columnas.
use Illuminate\Database\Schema\Blueprint;
// Consultas directas a la base.
use Illuminate\Support\Facades\DB;
// Fachada de esquema.
use Illuminate\Support\Facades\Schema;

// Ajusta bases de datos creadas con versiones anteriores de HydroPay y agrega las reglas de integridad.
// Es condicional: revisa antes de agregar, así que no duplica columnas ni índices existentes.
return new class extends Migration
{
    // Aplica los ajustes.
    public function up(): void
    {
        // Agrega el rol si la tabla users proviene de la migración original de Laravel.
        if (! Schema::hasColumn('users', 'rol')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('rol', 20)->default('consulta')->index();
            });
        }

        // Agrega las observaciones del recibo si todavía no existen.
        if (! Schema::hasColumn('cobros', 'descripcion')) {
            Schema::table('cobros', function (Blueprint $table) {
                $table->text('descripcion')->nullable();
            });
        }

        // Agrega el usuario que registró el pago, conservando el pago si se elimina ese usuario.
        if (! Schema::hasColumn('pagos', 'user_id')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        // Agrega la ruta privada del comprobante cuando la base anterior no la tenía.
        if (! Schema::hasColumn('pagos', 'comprobante')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->string('comprobante')->nullable();
            });
        }

        // REGLA: un solo pago por recibo → índice UNIQUE en pagos.cobro_id.
        if (! Schema::hasIndex('pagos', ['cobro_id'], 'unique')) {
            // Antes de crear el índice se comprueba que no existan recibos con más de un pago.
            $pagosDuplicados = DB::table('pagos')
                ->select('cobro_id')
                ->groupBy('cobro_id')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            // Si hay duplicados se detiene la migración con un mensaje claro.
            if ($pagosDuplicados) {
                throw new RuntimeException('Hay recibos con más de un pago. Corrige esos registros antes de aplicar la regla de pago único.');
            }

            // Crea el índice único.
            Schema::table('pagos', function (Blueprint $table) {
                $table->unique('cobro_id', 'pagos_cobro_id_unique');
            });
        }

        // REGLA: un cliente no recibe dos recibos del mismo periodo → UNIQUE(cliente_id, periodo).
        if (! Schema::hasIndex('cobros', ['cliente_id', 'periodo'], 'unique')) {
            // Comprueba que no existan duplicados previos.
            $periodosDuplicados = DB::table('cobros')
                ->select('cliente_id', 'periodo')
                ->groupBy('cliente_id', 'periodo')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            // Si los hay, se detiene con un mensaje claro.
            if ($periodosDuplicados) {
                throw new RuntimeException('Hay clientes con recibos duplicados en el mismo periodo. Corrige esos registros antes de continuar.');
            }

            // Crea el índice único compuesto.
            Schema::table('cobros', function (Blueprint $table) {
                $table->unique(['cliente_id', 'periodo'], 'cobros_cliente_id_periodo_unique');
            });
        }
    }

    // Revierte solo los índices (las columnas se conservan para no perder datos).
    public function down(): void
    {
        // Quita el índice único de pagos si existe.
        if (Schema::hasIndex('pagos', 'pagos_cobro_id_unique')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->dropUnique('pagos_cobro_id_unique');
            });
        }

        // Quita el índice único de cobros si existe.
        if (Schema::hasIndex('cobros', 'cobros_cliente_id_periodo_unique')) {
            Schema::table('cobros', function (Blueprint $table) {
                $table->dropUnique('cobros_cliente_id_periodo_unique');
            });
        }
    }
};
