<?php

// Espacio de nombres de los seeders.
namespace Database\Seeders;

// Modelos que se llenan con datos de demostración.
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Pago;
use App\Models\User;
// Catálogo de zonas.
use App\Models\Zona;
// Servicio que crea el recibo del periodo según la cuota de cada cliente.
use App\Services\GeneradorRecibosService;
// Clase base de los seeders.
use Illuminate\Database\Seeder;

// SEEDER PRINCIPAL: crea usuarios de demostración, clientes, recibos y algunos pagos completos.
// Se ejecuta con: php artisan db:seed  (no borra datos; solo agrega si las tablas están vacías).
class DatabaseSeeder extends Seeder
{
    // Lógica del seeder.
    public function run(): void
    {
        // Administrador de demostración (updateOrCreate evita duplicarlo si ya existe).
        User::updateOrCreate(
            ['email' => 'admin@hydropay.test'],            // Busca por correo.
            [
                'name' => 'Administrador HydroPay',          // Nombre visible.
                'password' => 'HydroPayAdmin2026!',          // El cast "hashed" la cifra al guardar.
                'rol' => User::ROL_ADMIN,                    // Rol con acceso total.
            ]
        );

        // Cajero de demostración (se guarda en variable porque registra los pagos de ejemplo).
        $cajero = User::updateOrCreate(
            ['email' => 'cajero@hydropay.test'],
            [
                'name' => 'Cajero HydroPay',
                'password' => 'HydroPayCajero2026!',
                'rol' => User::ROL_CAJERO,
            ]
        );

        // Usuario de solo consulta.
        User::updateOrCreate(
            ['email' => 'consulta@hydropay.test'],
            [
                'name' => 'Usuario de Consulta',
                'password' => 'HydroPayConsulta2026!',
                'rol' => User::ROL_CONSULTA,
            ]
        );

        // Zonas de ejemplo: cabecera y localidades del municipio de Tala, Jalisco.
        foreach (['Cabecera municipal', 'San Isidro Mazatepec', 'Cuisillos', 'El Refugio'] as $localidad) {
            Zona::firstOrCreate(['municipio' => 'Tala', 'localidad' => $localidad]);
        }
        $zonas = Zona::pluck('id');

        // 25 clientes ficticios con cuota mensual, más uno bimestral y uno anual, solo si el padrón está vacío.
        if (Cliente::count() === 0) {
            Cliente::factory()->count(25)->create()
                // Cuotas mensuales de ejemplo entre $150 y $300 y una zona al azar.
                ->each(fn (Cliente $c) => $c->update([
                    'tarifa' => fake()->randomElement([150, 180, 200, 250, 300]),
                    'zona_id' => $zonas->random(),
                ]));
            Cliente::factory()->conCuota(320, Cliente::BIMESTRAL)->create(['nombre' => 'Rancho El Mezquite (bimestral)', 'zona_id' => $zonas->random()]);
            Cliente::factory()->conCuota(1800, Cliente::ANUAL)->create(['nombre' => 'Escuela Primaria Benito Juárez (anual)', 'zona_id' => $zonas->first()]);
        }

        // Recibos de ejemplo, solo si todavía no existen.
        if (Cobro::count() === 0) {
            // Tres periodos con su fecha límite.
            $periodos = [
                ['nombre' => 'Septiembre 2026', 'vence' => '2026-09-10'],
                ['nombre' => 'Octubre 2026', 'vence' => '2026-10-10'],
                ['nombre' => 'Noviembre 2026', 'vence' => '2026-11-20'],
            ];

            // Un recibo por cliente mensual y por periodo (los bimestrales y anuales los crea el generador).
            foreach (Cliente::where('periodicidad', Cliente::MENSUAL)->get() as $indice => $cliente) {
                foreach ($periodos as $posicion => $periodo) {
                    // El importe representa el adeudo informado por el organismo operador.
                    $cobro = Cobro::factory()->create([
                        'cliente_id' => $cliente->id,
                        'periodo' => $periodo['nombre'],
                        'monto' => $cliente->tarifa ?? fake()->randomFloat(2, 180, 650), // Importe de su cuota.
                        'fecha_vencimiento' => $periodo['vence'],
                    ]);

                    // Algunos recibos se marcan como pagados con una regla fija (resultado repetible).
                    $debeEstarPagado = ($posicion === 0 && $indice % 2 === 0)
                        || ($posicion === 1 && $indice % 3 === 0)
                        || ($posicion === 2 && $indice % 5 === 0);

                    // Pago completo: exactamente el importe del recibo, sin parcialidades.
                    if ($debeEstarPagado) {
                        Pago::create([
                            'cobro_id' => $cobro->id,
                            'user_id' => $cajero->id,
                            'monto' => $cobro->monto,
                            'metodo' => fake()->randomElement(Pago::METODOS),
                            'referencia' => 'DEMO-'.$cobro->id,
                            // Fecha de pago: la fecha límite del periodo, o hoy si esa fecha aún no llega.
                            'fecha_pago' => min($periodo['vence'], now()->toDateString()),
                        ]);
                    }

                    // Calcula el estado según el pago y la fecha límite (pagado, vencido o pendiente).
                    $cobro->recalcularEstado();
                }
            }
        }

        // Recibos del periodo actual que falten (bimestrales, anuales y el mes en curso), igual que la tarea diaria.
        app(GeneradorRecibosService::class)->generar();
    }
}
