# =====================================================================
# INSTALADOR LOCAL DE HYDROPAY (Windows PowerShell / Laravel Herd)
# Uso (desde la carpeta raíz del proyecto):
#   Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process
#   .\instalar_hydropay.ps1
# No borra datos: solo crea lo que falta y ejecuta migraciones pendientes.
# =====================================================================

# Detiene el script ante errores de PowerShell; los de comandos externos se revisan con $LASTEXITCODE.
$ErrorActionPreference = 'Stop'

Write-Host "=== Instalación local de HydroPay 1.0.0 ===" -ForegroundColor Cyan

# Comprueba que PHP esté disponible (Herd lo agrega al PATH).
if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    throw "PHP no está disponible. Abre PowerShell desde Laravel Herd o agrega PHP al PATH."
}

# Comprueba que Composer esté disponible.
if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
    throw "Composer no está disponible. Instálalo o abre PowerShell desde Laravel Herd."
}

# 1. Dependencias de PHP definidas en composer.lock.
Write-Host "1/8 Instalando dependencias (composer install)..." -ForegroundColor Yellow
composer install
if ($LASTEXITCODE -ne 0) { throw "Falló composer install." }

# 2. Paquete adicional Laravel Sanctum (API). Solo se instala si todavía no está en vendor.
Write-Host "2/8 Verificando Laravel Sanctum..." -ForegroundColor Yellow
if (-not (Test-Path vendor\laravel\sanctum)) {
    composer require laravel/sanctum
    # Composer puede devolver error por un script posterior (boost:update) aunque Sanctum sí quedó instalado;
    # por eso se revisa la carpeta del paquete y no solo el código de salida.
    if (-not (Test-Path vendor\laravel\sanctum)) { throw "No se pudo instalar laravel/sanctum. Revisa tu conexión a internet." }
} else {
    Write-Host "    Sanctum ya está instalado." -ForegroundColor Green
}

# 3. Archivo .env: se crea solo si no existe (nunca reemplaza tu configuración).
Write-Host "3/8 Preparando .env..." -ForegroundColor Yellow
if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
}

# 4. Base SQLite vacía: se crea solo si no existe.
Write-Host "4/8 Preparando base SQLite..." -ForegroundColor Yellow
if (-not (Test-Path database\database.sqlite)) {
    New-Item -ItemType File -Path database\database.sqlite | Out-Null
}

# 5. Clave de la aplicación: solo si APP_KEY está vacía.
$contenidoEnv = Get-Content .env -Raw
if ($contenidoEnv -match '(?m)^APP_KEY=\s*$') {
    Write-Host "5/8 Generando APP_KEY..." -ForegroundColor Yellow
    php artisan key:generate
    if ($LASTEXITCODE -ne 0) { throw "No se pudo generar APP_KEY." }
} else {
    Write-Host "5/8 APP_KEY ya existe; se conserva." -ForegroundColor Green
}

# 6. Migraciones pendientes (incluye la tabla de tokens de la API).
Write-Host "6/8 Ejecutando migraciones..." -ForegroundColor Yellow
php artisan migrate
if ($LASTEXITCODE -ne 0) { throw "Fallaron las migraciones. Revisa el mensaje de Laravel." }

# 7. Datos de demostración (solo agrega si las tablas están vacías).
Write-Host "7/8 Cargando datos de demostración..." -ForegroundColor Yellow
php artisan db:seed
if ($LASTEXITCODE -ne 0) { throw "Falló el seeder." }

# 8. Limpieza de cachés y pruebas automatizadas.
Write-Host "8/8 Limpiando cachés y ejecutando pruebas..." -ForegroundColor Yellow
php artisan optimize:clear
php artisan test

Write-Host ""
Write-Host "HydroPay quedó preparado." -ForegroundColor Green
Write-Host "  Servidor:  php artisan serve   ->  http://127.0.0.1:8000" -ForegroundColor Cyan
Write-Host "  API:       http://127.0.0.1:8000/api/v1  (colección Postman en docs\postman)" -ForegroundColor Cyan
Write-Host "  Admin:     admin@hydropay.test / HydroPayAdmin2026!" -ForegroundColor Cyan
