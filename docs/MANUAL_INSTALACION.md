# Manual de instalación de HydroPay 1.0.0

## 1. Requisitos

| Componente | Versión | Notas |
|---|---|---|
| PHP | 8.4 (mínimo 8.4.1 por las dependencias bloqueadas en `composer.lock`) | Laravel Herd lo incluye en Windows |
| Extensiones PHP | `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `fileinfo`, `intl` | Activas por defecto en Herd |
| Composer | 2.x | Incluido en Herd |
| Git | 2.x | Para clonar y versionar |
| Visual Studio Code | Reciente | Editor; el proyecto trae `.vscode/` con extensiones recomendadas |
| GitHub CLI (`gh`) | 2.x | Issues, Pull Request y Release desde la terminal |
| Navegador | Chrome, Edge o Firefox recientes | |
| Postman (opcional) | Cualquier versión reciente | Pruebas de la API |

No se necesita Node.js ni npm: la interfaz usa una hoja de estilos y un archivo JavaScript propios en `public/`.

## 2. Instalación automática (Windows)

```powershell
# Desde la carpeta raíz del proyecto
Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process
.\scripts\preparar_entorno.ps1   # 1) Equipo: Git, VS Code y extensiones, GitHub CLI, Postman, revisión de PHP 8.4
.\instalar_hydropay.ps1           # 2) Proyecto: dependencias, Sanctum, .env, base, migraciones, datos y pruebas
code .                            # 3) Abrir el proyecto en Visual Studio Code
php artisan serve                 # 4) http://127.0.0.1:8000
```

`preparar_entorno.ps1` solo instala lo que falta (con `winget`), comprueba que el PHP activo sea el 8.4 de Herd y no el de XAMPP, instala las extensiones de VS Code, configura tu nombre y correo en Git e inicia sesión en GitHub. Si la carpeta está dentro de `%USERPROFILE%\Herd`, Herd también la sirve en `http://hydropay.test`.

El script instala dependencias, instala Laravel Sanctum si falta, crea `.env` y la base SQLite (solo si no existen), genera `APP_KEY`, ejecuta migraciones y seeders, limpia cachés y corre las pruebas. Nunca borra datos.

## 3. Instalación manual paso a paso

```powershell
# 1. Obtener el código
git clone https://github.com/USUARIO/HydroPay.git
cd HydroPay

# 2. Dependencias de PHP (incluye laravel/sanctum)
composer install

# 3. Archivo de configuración local
Copy-Item .env.example .env

# 4. Clave de cifrado de la aplicación
php artisan key:generate

# 5. Base de datos SQLite vacía
New-Item -ItemType File database\database.sqlite

# 6. Tablas (incluye personal_access_tokens de la API)
php artisan migrate

# 7. Datos ficticios de demostración (3 usuarios, 27 clientes con cuota, 77 recibos)
php artisan db:seed

# 8. Iniciar el servidor
php artisan serve
```

Abrir `http://127.0.0.1:8000`.

> **Actualizar una copia anterior de HydroPay (actividades 5 y 6):** copia los archivos nuevos sobre tu carpeta, conserva tu `.env`, y ejecuta `composer require laravel/sanctum` (una sola vez), `composer dump-autoload`, `php artisan migrate` y `php artisan optimize:clear`. Tus datos se conservan.

## 4. Configuración (`.env`)

| Variable | Valor de desarrollo | En producción |
|---|---|---|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` (no mostrar errores técnicos) |
| `APP_URL` | `http://127.0.0.1:8000` | Dominio con HTTPS |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | `es` / `en` | Igual |
| `DB_CONNECTION` | `sqlite` | `mysql` o `pgsql` con usuario propio |
| `DB_DATABASE` | Omitida (usa `database/database.sqlite`) | Nombre de la base |
| `SESSION_ENCRYPT` | `false` | `true` (+ `SESSION_SECURE_COOKIE=true`) |
| `MAIL_MAILER` | `log` (correos en `storage/logs`) | `smtp` con credenciales institucionales |
| `LOG_STACK` | `daily` | `daily` |
| `HYDROPAY_ORGANISMO` | Nombre del organismo | Igual |
| `HYDROPAY_API_TOKEN_HORAS` | `8` | Según política del organismo |

Reglas de seguridad del archivo `.env`:

- **Nunca se sube a GitHub** (está en `.gitignore`). Comprobar con `git ls-files .env` (no debe mostrar nada).
- Cada línea `CLAVE=valor` va en su propio renglón. Si falta un salto de línea Laravel lee mal el valor (error real registrado el 17/11/2026: `Database file at path [database/database.sqliteAPP_NAME=HydroPay] does not exist`).
- Si en algún momento se publicó, regenerar la clave con `php artisan key:generate`.
- `.env.example` es la plantilla pública: contiene los nombres de las variables, nunca contraseñas reales.

## 5. Base de datos y migraciones

```powershell
php artisan migrate:status   # Estado de cada migración
php artisan migrate          # Aplica las pendientes sin borrar datos
php artisan db:seed          # Agrega datos de demostración si las tablas están vacías
```

| Migración | Crea o ajusta |
|---|---|
| `0001_01_01_000000_create_users_table` | `users`, `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table` | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` |
| `2026_10_05_225152_create_clientes_table` | `clientes` |
| `2026_10_05_225153_create_cobros_table` | `cobros` |
| `2026_10_05_225306_create_pagos_table` | `pagos` |
| `2026_11_09_000001_ajustar_integridad_hydropay` | Índices únicos `pagos.cobro_id` y `cobros(cliente_id, periodo)`; columnas faltantes de versiones anteriores |
| `2026_11_21_000001_create_personal_access_tokens_table` | Tokens de la API (Sanctum) |
| `2026_11_22_000001_agregar_cuota_a_clientes_table` | Periodicidad y cuota de cada cliente |

> `php artisan migrate:fresh --seed` **borra todas las tablas**. Úsalo solo en una base desechable.

Para MySQL/MariaDB: crear la base `hydropay` con `utf8mb4`, cambiar `DB_*` en `.env` y ejecutar `php artisan migrate --seed`.

## 6. Ejecución y tareas

```powershell
php artisan serve                       # Servidor de desarrollo
php artisan schedule:work               # (otra terminal) Vencimientos diarios y limpieza de tokens
php artisan hydropay:marcar-vencidos    # Marcar vencidos manualmente
php artisan route:list                  # Rutas web y API
php artisan test                        # Pruebas automatizadas
php artisan pail                        # Ver el log en tiempo real (opcional)
```

## 7. Usuarios de demostración

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@hydropay.test` | `HydroPayAdmin2026!` |
| Cajero | `cajero@hydropay.test` | `HydroPayCajero2026!` |
| Consulta | `consulta@hydropay.test` | `HydroPayConsulta2026!` |

Son cuentas ficticias. Antes de cualquier uso real se cambian las contraseñas o se eliminan.

## 8. Solución de problemas

| Síntoma | Causa | Solución |
|---|---|---|
| `Your Composer dependencies require a PHP version ">= 8.4.1"` | PHP 8.3 o anterior | Usar PHP 8.4 de Herd |
| `php -v` muestra 8.2 aunque Herd tiene 8.4 | La carpeta `C:\xampp\php` está antes en la variable `Path` | Quitarla de `Path` (Buscar › Variables de entorno) y abrir otra terminal; `preparar_entorno.ps1` lo detecta |
| `no se puede cargar ... porque la ejecución de scripts está deshabilitada` | Política de ejecución de PowerShell | `Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process` en la misma terminal |
| `git push` pide usuario y contraseña | Git sin sesión de GitHub | `gh auth login --web` y luego `gh auth setup-git` |
| `Trait "Laravel\Sanctum\HasApiTokens" not found` | No se instaló Sanctum | `composer require laravel/sanctum` |
| `Call to undefined function formato_moneda()` | Autoload sin regenerar | `composer dump-autoload` |
| `Please provide a valid cache path.` | Faltan carpetas en `storage/framework` | Crear `storage/framework/{cache,sessions,views}` o volver a copiar el proyecto (ya incluye sus `.gitignore`) |
| `Database file at path [...] does not exist` | Ruta o formato incorrecto en `DB_DATABASE` | Quitar `DB_DATABASE` o corregir la línea |
| Página 419 al enviar un formulario | La sesión expiró | Recargar la página e iniciar sesión |
| No llega el correo de recuperación | `MAIL_MAILER=log` | Buscar el enlace en `storage/logs/laravel-AAAA-MM-DD.log` |
| La API responde 401 | Falta `Authorization: Bearer` o el token venció | Volver a `POST /api/v1/auth/login` |
