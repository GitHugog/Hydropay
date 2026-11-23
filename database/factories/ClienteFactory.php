<?php

// Espacio de nombres de las fábricas.
namespace Database\Factories;

// Modelo que fabrica.
use App\Models\Cliente;
// Clase base de las fábricas.
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * FÁBRICA DE CLIENTES: genera clientes de prueba con datos verosímiles (Faker en español de México).
 *
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    // Valores por defecto de cada cliente falso.
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),                                         // Nombre aleatorio.
            'numero_cuenta' => 'HP-'.fake()->unique()->numerify('######'),     // Cuenta única: HP-000123.
            'direccion' => fake()->streetAddress().', '.fake()->city(),         // Calle y ciudad.
            'telefono' => fake()->optional(0.8)->numerify('33########'),        // 80 % con teléfono de 10 dígitos.
            'email' => fake()->optional(0.7)->safeEmail(),                      // 70 % con correo.
            'activo' => fake()->boolean(90),                                    // 90 % activos.
            'periodicidad' => Cliente::MENSUAL,                                 // Cobro mensual por defecto.
            'tarifa' => null,                                                   // Sin cuota: recibos manuales.
        ];
    }

    // Estado con cuota asignada: Cliente::factory()->conCuota(150, 'bimestral')->create().
    public function conCuota(float $tarifa = 150, string $periodicidad = Cliente::MENSUAL): static
    {
        return $this->state(fn () => ['activo' => true, 'tarifa' => $tarifa, 'periodicidad' => $periodicidad]);
    }

    // Estado para crear un cliente inactivo: Cliente::factory()->inactivo()->create().
    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
