<?php

// Espacio de nombres de las fábricas.
namespace Database\Factories;

// Modelos involucrados.
use App\Models\Cliente;
use App\Models\Cobro;
// Clase base de las fábricas.
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * FÁBRICA DE RECIBOS: si no se indica cliente, crea uno automáticamente.
 *
 * @extends Factory<Cobro>
 */
class CobroFactory extends Factory
{
    // Contador compartido: cada recibo fabricado recibe un periodo distinto (Enero 2024, Febrero 2024...).
    protected static int $consecutivo = 0;

    // Valores por defecto de cada recibo falso.
    public function definition(): array
    {
        // Mes (1-12) y año del periodo según el consecutivo; así nunca se repite para el mismo cliente.
        $mes = (static::$consecutivo % 12) + 1;
        $anio = 2024 + intdiv(static::$consecutivo, 12);
        static::$consecutivo++;

        return [
            'cliente_id' => Cliente::factory(),                                          // Cliente nuevo asociado.
            'periodo' => ucfirst(nombre_mes($mes)).' '.$anio,                            // Periodo único (regla cliente+periodo).
            'monto' => fake()->randomFloat(2, 80, 900),                                  // Importe con dos decimales.
            'fecha_vencimiento' => now()->addDays(fake()->numberBetween(5, 30))->toDateString(), // Vence en 5 a 30 días.
            'estado' => Cobro::PENDIENTE,                                                // Nace sin pagar.
            'descripcion' => fake()->optional()->sentence(),                             // Observación opcional.
        ];
    }

    // Estado para crear un recibo ya vencido: Cobro::factory()->vencido()->create().
    public function vencido(): static
    {
        return $this->state(fn () => [
            'fecha_vencimiento' => now()->subDays(5)->toDateString(), // Venció hace 5 días.
            'estado' => Cobro::VENCIDO,
        ]);
    }
}
