<?php

// Espacio de nombres de las fábricas.
namespace Database\Factories;

// Modelo que genera esta fábrica.
use App\Models\Zona;
// Clase base de las fábricas.
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * FÁBRICA DE ZONAS para pruebas: Zona::factory()->create().
 *
 * @extends Factory<Zona>
 */
class ZonaFactory extends Factory
{
    // Modelo asociado.
    protected $model = Zona::class;

    // Valores por defecto: municipio y localidad ficticios y únicos.
    public function definition(): array
    {
        return [
            'municipio' => fake()->city(),                          // Municipio ficticio.
            'localidad' => 'Localidad '.fake()->unique()->numberBetween(1, 99999), // Localidad única.
        ];
    }
}
