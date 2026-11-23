# 💧 HydroPay

**Sistema web para la administración de clientes, recibos y pagos de servicios de agua**, pensado para organismos operadores y comités de agua de localidades alejadas de las cabeceras municipales.

![Versión](https://img.shields.io/badge/versión-1.0.0-0879bd) ![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20) ![PHP](https://img.shields.io/badge/PHP-8.4-777BB4) ![Pruebas](https://img.shields.io/badge/pruebas-100%20Pest-16805d)

## Características

- **Clientes:** padrón con búsqueda, filtro activo/inactivo, detalle y adeudo actual.
- **Recibos (cobros):** un recibo por periodo y cliente; estados *Sin pagar*, *Vencido*, *Pagado* y *Cancelado*; vencimiento automático diario.
- **Pagos:** un **solo pago completo** por recibo (sin abonos ni pagos dobles, incluso con varios cajeros), comprobante privado, impresión y correo de confirmación al cliente.
- **Reportes mensuales:** ingresos por método de pago, recibos por estado, adeudo vigente, principales deudores y exportación a **CSV**.
- **Usuarios y roles:** administrador, cajero y consulta, con middleware propio `rol:` y Policies.
- **API REST v1** con **Laravel Sanctum**: tokens con habilidades por rol, mensajes de error en español y colección Postman.
- **Interfaz responsiva** con menú desplegable en celular, páginas de error propias y textos en español.

## Tecnologías

Laravel 13 · PHP 8.4 · Eloquent · Blade (parciales y componentes) · SQLite (MySQL/PostgreSQL en producción) · Laravel Sanctum 4 · Pest 5.

## Requisitos

PHP **8.4** con `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo` e `intl` · Composer 2 · Git. En Windows, Laravel Herd incluye todo. No se necesita Node.js.

## Instalación

Instalación automática en Windows:

```powershell
Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process
.\scripts\preparar_entorno.ps1   # Equipo: Git, VS Code + extensiones, GitHub CLI, Postman, PHP 8.4
.\instalar_hydropay.ps1           # Proyecto: dependencias, .env, base, migraciones, datos y pruebas
```

Publicación en GitHub: `.\scripts\git_version_final.ps1` (commits por tema) y `.\scripts\publicar_github.ps1` (issues, Pull Request, etiqueta y Release).

Instalación manual:

```powershell
git clone https://github.com/USUARIO/HydroPay.git
cd HydroPay
composer install                                  # Dependencias (incluye laravel/sanctum)
Copy-Item .env.example .env                       # Configuración local
php artisan key:generate                          # Clave de la aplicación
New-Item -ItemType File database\database.sqlite  # Base SQLite vacía
```

## Configuración

Edita `.env` (nunca se sube a GitHub). Variables principales:

| Variable | Desarrollo | Producción |
|---|---|---|
| `APP_ENV` / `APP_DEBUG` | `local` / `true` | `production` / `false` |
| `APP_LOCALE` | `es` | `es` |
| `DB_CONNECTION` | `sqlite` | `mysql` o `pgsql` + `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| `MAIL_MAILER` | `log` (los correos quedan en `storage/logs`) | `smtp` |
| `HYDROPAY_ORGANISMO` | Nombre que aparece en reportes | Igual |
| `HYDROPAY_API_TOKEN_HORAS` | `8` | Según política |

## Base de datos y migraciones

```powershell
php artisan migrate          # Crea las tablas (incluye personal_access_tokens de la API)
php artisan db:seed          # Datos ficticios: 3 usuarios, 27 clientes con cuota (mensual, bimestral y anual), 77 recibos y pagos
php artisan migrate:status   # Estado de las migraciones
```

> `php artisan migrate:fresh --seed` **borra todas las tablas**: úsalo solo en una base desechable.

Diagrama entidad-relación: [`docs/diagrama_er.png`](docs/diagrama_er.png) · Diccionario: [`docs/DICCIONARIO_DATOS.md`](docs/DICCIONARIO_DATOS.md).

## Ejecución

```powershell
php artisan serve            # http://127.0.0.1:8000
php artisan schedule:work    # (otra terminal) vencimientos diarios y limpieza de tokens
```

### Usuarios de demostración

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@hydropay.test` | `HydroPayAdmin2026!` |
| Cajero | `cajero@hydropay.test` | `HydroPayCajero2026!` |
| Consulta | `consulta@hydropay.test` | `HydroPayConsulta2026!` |

Cuentas ficticias: cámbialas o elimínalas antes de cualquier uso real.

## API REST

URL base `http://127.0.0.1:8000/api/v1` · token Bearer de Sanctum.

```bash
POST /auth/login           # { "email": "...", "password": "..." } → token
GET  /clientes             # Authorization: Bearer {token}
GET  /clientes/{id}/estado-cuenta
POST /pagos                # { "cobro_id": 1, "metodo": "efectivo", "fecha_pago": "2026-11-21" }
```

Referencia completa: [`docs/API.md`](docs/API.md) · Postman: [`docs/postman/`](docs/postman/).

## Pruebas

```powershell
php artisan test                 # 112 pruebas (web, API, reportes, errores y helpers)
php artisan test --filter=Api    # Solo la API
```

Casos de prueba documentados: [`docs/CASOS_DE_PRUEBA.md`](docs/CASOS_DE_PRUEBA.md).

## Estructura

```text
app/Http/Controllers      Controladores web       app/Http/Controllers/Api   Controladores de la API
app/Http/Requests         Validación compartida   app/Http/Middleware        VerificarRol (rol:...)
app/Policies              Permisos por registro   app/Services               Pagos, resumen, reportes
app/Models                Eloquent                app/Http/Resources         JSON de la API
resources/views           Blade (layouts, parciales, componentes, módulos, errores)
routes/web.php · api.php · console.php            lang/es                    Traducciones
tests/Feature · tests/Unit                        docs/                      Documentación
```

## Documentación

| Documento | Contenido |
|---|---|
| [`docs/DOCUMENTACION_TECNICA.md`](docs/DOCUMENTACION_TECNICA.md) / `.docx` | Documento técnico final (secciones 1-23 y anexos) |
| [`docs/MANUAL_INSTALACION.md`](docs/MANUAL_INSTALACION.md) | Instalación, configuración y solución de problemas |
| [`docs/MANUAL_USUARIO.md`](docs/MANUAL_USUARIO.md) | Uso del sistema por rol |
| [`docs/API.md`](docs/API.md) | Endpoints, parámetros y códigos de respuesta |
| [`docs/CASOS_DE_PRUEBA.md`](docs/CASOS_DE_PRUEBA.md) | Matriz de pruebas |
| [`docs/BITACORA_IA.md`](docs/BITACORA_IA.md) | Uso de IA generativa |
| [`docs/GIT_GITHUB.md`](docs/GIT_GITHUB.md) | Commits, ramas, issues, release |
| [`docs/EVIDENCIAS.md`](docs/EVIDENCIAS.md) | Capturas requeridas y guion de demostración |
| [`CHANGELOG.md`](CHANGELOG.md) | Cambios por versión |

## Seguridad

Contraseñas con hash bcrypt · CSRF en todos los formularios · middleware por rol y Policies · límites de intentos · tokens de API con vencimiento y habilidades · comprobantes en almacenamiento privado · `.env` fuera del repositorio. Para producción revisa la sección 13.9 del documento técnico.

## Licencia

Proyecto académico. Laravel y sus paquetes se distribuyen bajo licencia MIT.
