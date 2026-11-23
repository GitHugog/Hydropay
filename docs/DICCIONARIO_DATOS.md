# Diccionario de datos de HydroPay

Motor de desarrollo: SQLite (compatible con MySQL/MariaDB y PostgreSQL mediante las migraciones de Laravel). Codificación UTF-8. Diagrama: `docs/diagrama_er.png` (fuente `docs/diagramas/er.mmd`).

## Tabla `clientes` — titulares de una cuenta del servicio

| Campo | Tipo | Nulo | Llave / índice | Descripción | Validación en la aplicación |
|---|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador | — |
| `nombre` | varchar(120) | No | | Nombre del titular | obligatorio, máx. 120, sin HTML |
| `numero_cuenta` | varchar(30) | No | UNIQUE | Cuenta del servicio | obligatorio, máx. 30, único |
| `direccion` | varchar(255) | No | | Domicilio del servicio | obligatorio, máx. 255 |
| `zona_id` | bigint | Sí | INDEX → `zonas.id` | Zona (municipio y localidad) | debe existir en `zonas`; integridad cuidada por la aplicación (SQLite no agrega FK a tablas existentes) |
| `telefono` | varchar(20) | Sí | | Teléfono de contacto | 7-20 caracteres: dígitos, espacio, `+ - ( )` |
| `email` | varchar(255) | Sí | | Correo para avisos de pago | formato de correo |
| `activo` | boolean (def. 1) | No | | Registro habilitado | booleano |
| `periodicidad` | varchar(10) (def. `mensual`) | No | | Cada cuánto se le cobra | `mensual`, `bimestral` o `anual`; solo administrador |
| `tarifa` | decimal(8,2) | Sí | | Cuota por periodo; null = sin recibos automáticos | numérico, 0 a 999,999.99, máx. 2 decimales; solo administrador |
| `created_at`, `updated_at` | timestamp | Sí | | Auditoría de fechas | automático |

## Tabla `zonas` — municipios y localidades (catálogo del administrador)

| Campo | Tipo | Nulo | Llave / índice | Descripción | Validación en la aplicación |
|---|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador | — |
| `municipio` | varchar(120) | No | UNIQUE (`municipio`, `localidad`) | Municipio, por ejemplo *Tala* | obligatorio, máx. 120, sin HTML |
| `localidad` | varchar(120) | No | UNIQUE (`municipio`, `localidad`) | Pueblo, colonia o ranchería | obligatorio, máx. 120, única dentro del municipio |
| `created_at`, `updated_at` | timestamp | Sí | | Auditoría de fechas | automático |

## Tabla `cobros` — recibos o adeudos

| Campo | Tipo | Nulo | Llave / índice | Descripción | Validación en la aplicación |
|---|---|---|---|---|---|
| `id` | bigint | No | PK | Identificador del recibo | — |
| `cliente_id` | bigint | No | FK → `clientes.id` (RESTRICT) | Cliente dueño del recibo | existe; al crear, cliente activo |
| `periodo` | varchar(30) | No | UNIQUE(`cliente_id`, `periodo`) | Periodo o concepto | obligatorio, máx. 30, único por cliente |
| `monto` | decimal(10,2) | No | | Importe total | > 0, máx. dos decimales, ≤ 99,999,999.99 |
| `fecha_vencimiento` | date | Sí | | Fecha límite | fecha válida |
| `estado` | varchar(20) (def. `pendiente`) | No | índice | `pendiente`, `vencido`, `pagado`, `cancelado` | al editar solo `pendiente`/`cancelado`; el resto lo calcula el sistema |
| `descripcion` | text | Sí | | Observaciones | máx. 500, sin HTML |
| `created_at`, `updated_at` | timestamp | Sí | | Fechas | automático |

## Tabla `pagos` — liquidación completa de un recibo

| Campo | Tipo | Nulo | Llave / índice | Descripción | Validación en la aplicación |
|---|---|---|---|---|---|
| `id` | bigint | No | PK | Folio del pago | — |
| `cobro_id` | bigint | No | FK → `cobros.id` (RESTRICT) + UNIQUE | Recibo liquidado (uno a cero/uno) | existe; recibo pendiente o vencido sin pago |
| `user_id` | bigint | Sí | FK → `users.id` (SET NULL) | Usuario que registró | automático (usuario autenticado) |
| `monto` | decimal(10,2) | No | | Importe recibido | se copia del recibo; si se envía debe coincidir |
| `metodo` | varchar(40) | No | | `efectivo`, `transferencia`, `tarjeta`, `deposito` | lista cerrada |
| `referencia` | varchar(255) | Sí | | Folio u operación bancaria | máx. 100, sin HTML |
| `fecha_pago` | date | No | | Fecha de recepción | fecha ≤ hoy |
| `comprobante` | varchar(255) | Sí | | Ruta en `storage/app/private/comprobantes` | JPG/PNG/PDF ≤ 2 MB |
| `created_at`, `updated_at` | timestamp | Sí | | Fechas | automático |

## Tabla `users` — personal del sistema

| Campo | Tipo | Nulo | Llave / índice | Descripción |
|---|---|---|---|---|
| `id` | bigint | No | PK | Identificador |
| `name` | varchar(255) | No | | Nombre visible |
| `email` | varchar(255) | No | UNIQUE | Correo de acceso (minúsculas) |
| `email_verified_at` | timestamp | Sí | | Verificación del correo |
| `password` | varchar(255) | No | | Hash bcrypt (cast `hashed`) |
| `rol` | varchar(20) (def. `consulta`) | No | índice | `admin`, `cajero`, `consulta` |
| `remember_token` | varchar(100) | Sí | | Sesión recordada |
| `created_at`, `updated_at` | timestamp | Sí | | Fechas |

## Tabla `personal_access_tokens` — tokens de la API (Laravel Sanctum)

| Campo | Tipo | Nulo | Llave / índice | Descripción |
|---|---|---|---|---|
| `id` | bigint | No | PK | Identificador |
| `tokenable_type`, `tokenable_id` | varchar, bigint | No | índice compuesto | Dueño del token (`App\Models\User`, id) |
| `name` | text | No | | Dispositivo ("Postman", "App móvil") |
| `token` | varchar(64) | No | UNIQUE | Hash SHA-256 del token (el texto plano nunca se guarda) |
| `abilities` | text (JSON) | Sí | | `["leer","escribir","eliminar"]` |
| `last_used_at` | timestamp | Sí | | Último uso |
| `expires_at` | timestamp | Sí | índice | Vencimiento (8 horas) |
| `created_at`, `updated_at` | timestamp | Sí | | Fechas |

## Tablas técnicas de Laravel

| Tabla | Uso en HydroPay |
|---|---|
| `password_reset_tokens` | Tokens de recuperación de contraseña (vencen en 60 min) |
| `sessions` | Sesiones activas (`SESSION_DRIVER=database`) |
| `cache`, `cache_locks` | Caché del resumen del panel (`CACHE_STORE=database`) |
| `jobs`, `job_batches`, `failed_jobs` | Colas (listas para enviar correos en segundo plano) |
| `migrations` | Control de migraciones aplicadas |

## Relaciones

| Relación | Cardinalidad | Eloquent |
|---|---|---|
| Zona → Clientes | 1 : N (cliente sin zona permitido) | `Zona::clientes()` / `Cliente::zona()` |
| Cliente → Cobros | 1 : N | `Cliente::cobros()` / `Cobro::cliente()` |
| Cobro → Pago | 1 : 0..1 | `Cobro::pago()` / `Pago::cobro()` |
| Cliente → Pagos (a través de cobros) | 1 : N | `Cliente::pagos()` (hasManyThrough) |
| Usuario → Pagos registrados | 1 : N | `User::pagos()` / `Pago::registradoPor()` |
| Usuario → Tokens de API | 1 : N (polimórfica) | `User::tokens()` (Sanctum) |
