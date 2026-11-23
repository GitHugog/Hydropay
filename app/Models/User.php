<?php

// Espacio de nombres de los modelos Eloquent.
namespace App\Models;

// Fábrica que genera usuarios falsos para pruebas y seeders.
use Database\Factories\UserFactory;
// Atributo de PHP que declara los campos asignables en masa.
use Illuminate\Database\Eloquent\Attributes\Fillable;
// Atributo de PHP que oculta campos al convertir el modelo a JSON.
use Illuminate\Database\Eloquent\Attributes\Hidden;
// Rasgo que conecta el modelo con su fábrica.
use Illuminate\Database\Eloquent\Factories\HasFactory;
// Tipo de relación uno a muchos.
use Illuminate\Database\Eloquent\Relations\HasMany;
// Clase base de Laravel para usuarios que inician sesión.
use Illuminate\Foundation\Auth\User as Authenticatable;
// Rasgo que permite enviar notificaciones (por ejemplo, el correo de recuperación).
use Illuminate\Notifications\Notifiable;
// Rasgo del paquete Laravel Sanctum: permite crear tokens personales para la API.
use Laravel\Sanctum\HasApiTokens;

// Campos que se pueden asignar con create() o update().
#[Fillable(['name', 'email', 'password', 'rol'])]
// Campos que nunca se devuelven al convertir el modelo a arreglo o JSON.
#[Hidden(['password', 'remember_token'])]
// Modelo del personal que inicia sesión en HydroPay (administrador, cajero o consulta).
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    // HasApiTokens: tokens de la API; HasFactory: fábricas; Notifiable: notificaciones.
    use HasApiTokens, HasFactory, Notifiable;

    // Rol con acceso total: usuarios, eliminaciones y cancelación de recibos.
    public const ROL_ADMIN = 'admin';

    // Rol de caja: registra y modifica clientes, recibos y pagos; no elimina.
    public const ROL_CAJERO = 'cajero';

    // Rol de solo lectura: consulta información y reportes.
    public const ROL_CONSULTA = 'consulta';

    // Lista completa de roles válidos; se usa para validar formularios.
    public const ROLES = [self::ROL_ADMIN, self::ROL_CAJERO, self::ROL_CONSULTA];

    /**
     * Conversiones automáticas de tipos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // La fecha de verificación del correo se maneja como objeto de fecha.
            'email_verified_at' => 'datetime',
            // "hashed" cifra la contraseña con bcrypt cada vez que se asigna (nunca se guarda en texto plano).
            'password' => 'hashed',
        ];
    }

    // Indica si el usuario tiene el rol de administrador.
    public function esAdmin(): bool
    {
        // Comparación estricta con la constante del rol.
        return $this->rol === self::ROL_ADMIN;
    }

    // Indica si el usuario puede registrar o modificar información (administrador o cajero).
    public function puedeOperar(): bool
    {
        // in_array con true compara también el tipo de dato.
        return in_array($this->rol, [self::ROL_ADMIN, self::ROL_CAJERO], true);
    }

    /**
     * Habilidades (abilities) que recibe el token de la API según el rol.
     * El middleware "abilities:..." de Sanctum las revisa en cada ruta de routes/api.php.
     *
     * @return array<int, string>
     */
    public function habilidadesApi(): array
    {
        // match devuelve el arreglo que corresponde al rol del usuario.
        return match ($this->rol) {
            // El administrador consulta, registra y elimina.
            self::ROL_ADMIN => ['leer', 'escribir', 'eliminar'],
            // El cajero consulta y registra.
            self::ROL_CAJERO => ['leer', 'escribir'],
            // Cualquier otro rol (consulta) solo puede leer.
            default => ['leer'],
        };
    }

    // Relación: un usuario puede haber registrado muchos pagos (columna pagos.user_id).
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }
}
