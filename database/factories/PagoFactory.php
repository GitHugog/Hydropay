<?php

// Espacio de nombres de las fábricas.
namespace Database\Factories;

// Modelos involucrados.
use App\Models\Cobro;
use App\Models\Pago;
use App\Models\User;
// Clase base de las fábricas.
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * FÁBRICA DE PAGOS: siempre por el importe completo de su recibo.
 *
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    // Valores por defecto de cada pago falso.
    public function definition(): array
    {
        return [
            'cobro_id' => Cobro::factory(),                              // Recibo nuevo si no se indica uno.
            'user_id' => User::factory(),                                // Usuario que registra.
            'monto' => 100,                                              // Se reemplaza por el monto del recibo (configure).
            'metodo' => fake()->randomElement(Pago::METODOS),            // Método aleatorio válido.
            'referencia' => fake()->optional()->bothify('REF-####??'),   // Referencia opcional.
            'fecha_pago' => now()->toDateString(),                       // Hoy.
            'comprobante' => null,                                       // Sin archivo.
        ];
    }

    // Después de construir el pago, iguala el monto al del recibo (nunca un abono parcial).
    public function configure(): static
    {
        return $this->afterMaking(function (Pago $pago) {
            // Recibo asociado.
            $cobro = $pago->cobro;

            // Copia el importe exacto.
            if ($cobro) {
                $pago->monto = $cobro->monto;
            }
        });
    }
}
