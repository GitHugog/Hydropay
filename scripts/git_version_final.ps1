# =====================================================================
# HYDROPAY — ORGANIZAR EL HISTORIAL GIT DE LA VERSIÓN FINAL (Actividad 7)
#
# Qué hace:
#   0. Verifica que estés en un repositorio Git y que Sanctum ya esté en composer.lock.
#   1. Quita del repositorio .env y la base SQLite si alguna vez se subieron (sin borrarlos de tu equipo).
#   2. Crea la rama feature/actividad-7-version-final.
#   3. Hace commits separados por tema (limpieza, dependencias, seguridad, MVC, reportes, API, UI, pruebas, docs).
#   4. Con -Publicar: sube la rama, la integra a main con merge --no-ff, crea la etiqueta v1.0.0 y la sube.
#
# Uso (desde la raíz del proyecto, después de copiar los archivos nuevos sobre tu repositorio):
#   composer require laravel/sanctum          # una sola vez, actualiza composer.json y composer.lock
#   .\scripts\git_version_final.ps1           # solo commits locales (revisa con: git log --oneline)
#   .\scripts\publicar_github.ps1           # recomendado: issues, Pull Request, merge, etiqueta y Release
#   .\scripts\git_version_final.ps1 -Publicar # alternativa sin GitHub CLI: merge local, etiqueta y push
# =====================================================================

# Parámetro opcional para publicar en GitHub.
param([switch]$Publicar)

# Los comandos de git escriben avisos en stderr; se revisa $LASTEXITCODE en lugar de detenerse.
$ErrorActionPreference = 'Continue'

# Rama de trabajo y versión final.
$rama = 'feature/actividad-7-version-final'
$version = 'v1.0.0'

# --- 0. Verificaciones previas ---
git rev-parse --is-inside-work-tree *> $null
if ($LASTEXITCODE -ne 0) { Write-Host "Esta carpeta no es un repositorio Git. Ejecuta el script dentro de tu repositorio de HydroPay." -ForegroundColor Red; exit 1 }

if (-not (Select-String -Path composer.lock -Pattern '"name": "laravel/sanctum"' -Quiet)) {
    Write-Host "Falta Sanctum en composer.lock. Ejecuta primero: composer require laravel/sanctum" -ForegroundColor Red; exit 1
}

# --- 1. Archivos sensibles que nunca deben estar en GitHub ---
foreach ($sensible in @('.env', 'database/database.sqlite')) {
    $rastreado = git ls-files -- $sensible
    if ($rastreado) {
        git rm --cached -- $sensible | Out-Host
        Write-Host "Se quitó $sensible del repositorio (tu archivo local se conserva)." -ForegroundColor Yellow
        if ($sensible -eq '.env') { Write-Host "IMPORTANTE: .env estuvo publicado. Regenera la clave con: php artisan key:generate" -ForegroundColor Yellow }
    }
}

# --- 2. Rama de trabajo ---
git switch -c $rama 2>$null
if ($LASTEXITCODE -ne 0) { git switch $rama | Out-Host }

# Agrega las rutas indicadas (incluidas eliminaciones) y crea un commit si hay cambios.
function Confirmar([string]$mensaje, [string[]]$rutas) {
    foreach ($ruta in $rutas) { git add -A -- $ruta 2>$null }
    $pendientes = git diff --cached --name-only
    if ($pendientes) {
        git commit -m $mensaje | Out-Host
    } else {
        Write-Host "Sin cambios para: $mensaje" -ForegroundColor DarkGray
    }
}

# --- 3. Commits por tema ---
Confirmar 'chore: eliminar codigo de ejemplo, Vite sin uso y archivos de asistentes de IA' @(
    '.gitignore', 'resources/views/welcome.blade.php', 'tests/Feature/ExampleTest.php', 'tests/Unit/ExampleTest.php',
    'package.json', 'package-lock.json', 'vite.config.js', '.npmrc', 'resources/js', 'resources/css',
    'AGENTS.md', 'CLAUDE.md', '.agents', '.codex', 'storage', 'bootstrap/cache/.gitignore', '.vscode')

Confirmar 'build(deps): agregar laravel/sanctum, helpers globales y configuracion de HydroPay' @(
    'composer.json', 'composer.lock', 'app/Support', 'config/hydropay.php', '.env.example')

Confirmar 'fix(i18n): traducciones completas en espanol y mensajes centralizados' @('lang')

Confirmar 'refactor(seguridad): middleware VerificarRol, policies con mensajes y registro de 403 reales' @(
    'app/Http/Middleware', 'app/Policies', 'app/Providers', 'bootstrap')

Confirmar 'feat(clientes): zonas por municipio y localidad, cuota por cliente y recibos automaticos' @(
    'app/Models/Zona.php', 'app/Http/Controllers/ZonaController.php', 'app/Http/Controllers/Api/ZonaController.php',
    'app/Http/Requests/ZonaRequest.php', 'app/Services/GeneradorRecibosService.php', 'app/Console/Commands/GenerarRecibos.php',
    'database/migrations/2026_11_22_000001_agregar_cuota_a_clientes_table.php', 'database/migrations/2026_11_22_000002_create_zonas_table.php',
    'database/factories/ZonaFactory.php', 'resources/views/zonas', 'tests/Feature/ZonaTest.php', 'tests/Feature/CuotaRecibosTest.php')

Confirmar 'refactor(mvc): Form Requests y servicio de cobranza compartidos por web y API' @(
    'app/Http/Requests', 'app/Services/RegistroPagoService.php', 'app/Services/ResumenService.php', 'app/Models',
    'app/Events', 'app/Listeners', 'app/Notifications', 'app/Console',
    'app/Http/Controllers/Controller.php', 'app/Http/Controllers/AuthController.php', 'app/Http/Controllers/ClienteController.php',
    'app/Http/Controllers/CobroController.php', 'app/Http/Controllers/CuentaController.php', 'app/Http/Controllers/DashboardController.php',
    'app/Http/Controllers/PagoController.php', 'app/Http/Controllers/RecuperarPasswordController.php', 'app/Http/Controllers/UsuarioController.php')

Confirmar 'feat(reportes): reporte mensual con rutas de parametros opcionales y exportacion CSV' @(
    'app/Services/ReporteMensualService.php', 'app/Http/Controllers/ReporteController.php', 'resources/views/reportes', 'routes/web.php')

Confirmar 'feat(api): API REST v1 con Laravel Sanctum, resources y coleccion Postman' @(
    'routes/api.php', 'routes/console.php', 'app/Http/Controllers/Api', 'app/Http/Resources',
    'database/migrations/2026_11_21_000001_create_personal_access_tokens_table.php', 'docs/postman')

Confirmar 'feat(ui): diseno azul agua con membrete, pestanas, buscador de clientes, componentes Blade y paginas de error' @('resources/views', 'public')

Confirmar 'chore(db): documentar migraciones, seeders y factories' @('database')

Confirmar 'test: pruebas de API, reportes, manejo de errores y helpers' @('tests', 'phpunit.xml')

Confirmar 'docs: README, manuales, API, bitacora de IA y documento tecnico final' @(
    'README.md', 'CHANGELOG.md', 'VERSION.txt', 'LEEME_PRIMERO.txt', 'instalar_hydropay.ps1', 'scripts', 'docs')

# Cualquier otro cambio restante.
Confirmar 'chore: ajustes finales de la version 1.0.0' @('.')

Write-Host ""
git log --oneline -n 15 | Out-Host

# --- 4. Publicación opcional ---
if ($Publicar) {
    # Rama principal del remoto (main o master).
    $principal = (git symbolic-ref --short refs/remotes/origin/HEAD 2>$null) -replace '^origin/', ''
    if (-not $principal) { $principal = 'main' }

    # Sube la rama de trabajo.
    git push -u origin $rama | Out-Host

    # Integra la rama en la principal conservando el historial (merge --no-ff).
    git switch $principal | Out-Host
    git pull origin $principal | Out-Host
    git merge --no-ff $rama -m "merge: integrar $rama (version final HydroPay)" | Out-Host
    if ($LASTEXITCODE -ne 0) { Write-Host "Hay conflictos: resuélvelos, haz commit y vuelve a ejecutar con -Publicar." -ForegroundColor Red; exit 1 }

    # Etiqueta anotada de la versión final.
    git tag -a $version -m "HydroPay $version - version final (Actividad 7)"

    # Sube la rama principal y las etiquetas.
    git push origin $principal | Out-Host
    git push origin $version | Out-Host

    Write-Host ""
    Write-Host "Listo. Crea el Release en GitHub:" -ForegroundColor Green
    Write-Host "  Opcion A (GitHub CLI): gh release create $version --title 'HydroPay $version' --notes-file CHANGELOG.md" -ForegroundColor Cyan
    Write-Host "  Opcion B (web): Repositorio > Releases > Draft a new release > Tag $version > pega el CHANGELOG." -ForegroundColor Cyan
} else {
    Write-Host ""
    Write-Host "Commits creados en la rama $rama." -ForegroundColor Green
    # Opción recomendada: issues, Pull Request, merge, etiqueta y Release con GitHub CLI.
    Write-Host "  Publicar con issues, Pull Request y Release (recomendado): .\scripts\publicar_github.ps1" -ForegroundColor Cyan
    # Opción sin GitHub CLI: merge local, etiqueta y push.
    Write-Host "  Publicar solo con git (merge local):                      .\scripts\git_version_final.ps1 -Publicar" -ForegroundColor Cyan
}

