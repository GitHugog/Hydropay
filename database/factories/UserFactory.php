<?php

// Espacio de nombres de las fábricas.
namespace Database\Factories;

// Modelo que fabrica.
use App\Models\User;
// Clase base de las fábricas.
use Illuminate\Database\Eloquent\Factories\Factory;
// Hash de contraseñas.
use Illuminate\Support\Facades\Hash;
// Utilidades de texto.
use Illuminate\Support\Str;

/**
 * FÁBRICA DE USUARIOS del sistema (por defecto con rol de consulta).
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    // Contraseña compartida: se calcula una sola vez para que las pruebas sean rápidas.
    protected static ?string $password;

    // Valores por defecto de cada usuario falso.
    public function definition(): array
    {
        return [
            'name' => fake()->name(),                                       // Nombre aleatorio.
            'email' => fake()->unique()->safeEmail(),                       // Correo único.
            'email_verified_at' => now(),                                   // Correo verificado.
            'password' => static::$password ??= Hash::make('password'),    // Contraseña "password" cifrada.
            'rol' => User::ROL_CONSULTA,                                    // Rol mínimo.
            'remember_token' => Str::random(10),                            // Token de sesión recordada.
        ];
    }

    // Estado: User::factory()->admin()->create().
    public function admin(): static
    {
        return $this->state(fn () => ['rol' => User::ROL_ADMIN]);
    }

    // Estado: User::factory()->cajero()->create().
    public function cajero(): static
    {
        return $this->state(fn () => ['rol' => User::ROL_CAJERO]);
    }

    // Estado: usuario con correo sin verificar.
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
