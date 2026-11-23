# =====================================================================
# HYDROPAY — PUBLICAR LA VERSIÓN FINAL EN GITHUB (Actividad 7)
#
# Antes ejecuta, en este orden:
#   .\scripts\preparar_entorno.ps1    (GitHub CLI con sesión iniciada)
#   .\instalar_hydropay.ps1           (pruebas en verde)
#   .\scripts\git_version_final.ps1   (rama feature/actividad-7-version-final con commits por tema)
#
# Qué hace (se puede repetir: salta lo que ya existe):
#   1. Conecta el repositorio local con GitHub (si no hay remoto "origin", crea el repositorio).
#   2. Registra en GitHub los issues de la revisión final.
#   3. Sube las ramas y abre un Pull Request que cierra esos issues.
#   4. Te pide confirmación, integra el Pull Request (merge commit) y crea la etiqueta v1.0.0.
#   5. Crea el Release v1.0.0 con las notas del CHANGELOG, el documento técnico y la colección Postman.
#
# Uso:  .\scripts\publicar_github.ps1
# =====================================================================

# Git y gh escriben avisos en stderr; cada paso revisa $LASTEXITCODE.
$ErrorActionPreference = 'Continue'

# Lee correctamente los acentos que devuelve gh.
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

# Rama de trabajo creada por git_version_final.ps1 y versión a publicar.
$rama = 'feature/actividad-7-version-final'
$version = 'v1.0.0'

# Muestra un error y termina el script.
function Fallar([string]$mensaje) {
    Write-Host $mensaje -ForegroundColor Red
    exit 1
}

# Imprime el título de cada paso.
function Paso([string]$texto) {
    Write-Host ''
    Write-Host "== $texto ==" -ForegroundColor Cyan
}

# Ejecuta un comando de gh que devuelve JSON y lo convierte a objetos de PowerShell.
function GhJson([string[]]$argumentos) {
    $salida = & gh @argumentos
    if (-not $salida) { return @() }
    return (($salida -join "`n") | ConvertFrom-Json)
}

# ---------------------------------------------------------------------
# 0. Verificaciones previas
# ---------------------------------------------------------------------
if (-not (Get-Command gh -ErrorAction SilentlyContinue)) { Fallar 'Falta GitHub CLI. Ejecuta primero .\scripts\preparar_entorno.ps1' }
gh auth status *> $null
if ($LASTEXITCODE -ne 0) { Fallar 'No has iniciado sesión en GitHub. Ejecuta: gh auth login --web' }

git rev-parse --is-inside-work-tree *> $null
if ($LASTEXITCODE -ne 0) { Fallar 'Esta carpeta no es un repositorio Git. Ejecuta el script en la raíz de HydroPay.' }

git show-ref --verify --quiet "refs/heads/$rama"
if ($LASTEXITCODE -ne 0) { Fallar "No existe la rama $rama. Ejecuta primero .\scripts\git_version_final.ps1" }

# No se publica nada con cambios sin guardar en un commit.
if (git status --porcelain) { Fallar 'Hay cambios sin commit. Ejecuta otra vez .\scripts\git_version_final.ps1 y repite.' }

# Rama principal local: main o master (la que exista).
$principal = $null
foreach ($candidata in @('main', 'master')) {
    git show-ref --verify --quiet "refs/heads/$candidata"
    if ($LASTEXITCODE -eq 0) { $principal = $candidata; break }
}
if (-not $principal) {
    # Repositorio sin rama principal (todo quedó en la rama de trabajo): main nace en el primer commit.
    $primerCommit = git rev-list --max-parents=0 $rama | Select-Object -First 1
    git branch main $primerCommit
    $principal = 'main'
    Write-Host 'Se creó la rama main a partir del primer commit.' -ForegroundColor Yellow
}

# ---------------------------------------------------------------------
# 1. Repositorio en GitHub
# ---------------------------------------------------------------------
Paso '1/5 Repositorio en GitHub'
$origen = git remote get-url origin 2>$null
if (-not $origen) {
    # No hay remoto: se crea el repositorio en tu cuenta.
    $nombre = Read-Host '  Nombre del repositorio nuevo (Enter = HydroPay)'
    if (-not $nombre) { $nombre = 'HydroPay' }
    $tipo = Read-Host '  Escribe "privado" para un repositorio privado o deja vacío para público'
    $visibilidad = if ($tipo -match '^priv') { '--private' } else { '--public' }
    gh repo create $nombre $visibilidad --source . --remote origin --description 'Sistema web de cobranza del servicio de agua potable (Laravel 13)'
    if ($LASTEXITCODE -ne 0) {
        Fallar "No se pudo crear el repositorio. Si ya existe en GitHub, conéctalo con: git remote add origin https://github.com/TU_USUARIO/$nombre.git"
    }
    $origen = git remote get-url origin
}
Write-Host "  Remoto: $origen" -ForegroundColor Green

# Sube la rama principal (si GitHub ya la tiene igual, no cambia nada).
git push -u origin $principal
if ($LASTEXITCODE -ne 0) {
    Fallar "GitHub rechazó $principal (tiene commits que tu equipo no). Ejecuta: git switch $principal; git pull origin $principal; git switch $rama; y repite."
}

# ---------------------------------------------------------------------
# 2. Issues de la revisión final
# ---------------------------------------------------------------------
Paso '2/5 Issues'
# La etiqueta "chore" no existe por defecto en GitHub; se crea (o actualiza) antes de usarla.
gh label create chore --color BFD4F2 --description 'Limpieza y mantenimiento' --force *> $null

# Problemas encontrados en la revisión final (títulos sin acentos para compararlos sin errores).
$issues = @(
    @{ Titulo = 'Mensajes de validacion sin traducir (validation.decimal)'; Etiqueta = 'bug'; Cuerpo = 'Las reglas decimal e integer mostraban la clave del mensaje en lugar del texto en español.' },
    @{ Titulo = 'Log con avisos falsos de autorizacion denegada'; Etiqueta = 'bug'; Cuerpo = 'Gate::after registraba cada @can de las vistas como acceso denegado (31 avisos el 17/11/2026).' },
    @{ Titulo = 'Error de cache path en instalaciones nuevas'; Etiqueta = 'bug'; Cuerpo = 'Faltaban las carpetas storage/framework/cache, sessions y views en el repositorio (Please provide a valid cache path).' },
    @{ Titulo = 'Comando de vencidos puede saltar registros'; Etiqueta = 'bug'; Cuerpo = 'chunk() con where sobre la columna que se modifica omite filas; se cambia a lazyById().' },
    @{ Titulo = 'Modulo de reportes mensuales'; Etiqueta = 'enhancement'; Cuerpo = 'Ingresos del mes, cobros pendientes, clientes y exportación CSV (requisito original del proyecto).' },
    @{ Titulo = 'API REST con autenticacion por token'; Etiqueta = 'enhancement'; Cuerpo = 'API v1 con Laravel Sanctum, habilidades por rol y colección Postman.' },
    @{ Titulo = 'Menu inutilizable en celular y paginas de error en ingles'; Etiqueta = 'enhancement'; Cuerpo = 'Menú desplegable, componentes Blade y vistas 403, 404, 419, 429, 500 y 503 en español.' },
    @{ Titulo = 'Cuota por cliente y recibos automaticos'; Etiqueta = 'enhancement'; Cuerpo = 'El administrador asigna periodicidad (mensual, bimestral o anual) y cuota; el sistema genera el recibo de cada periodo y el cajero solo cobra.' },
    @{ Titulo = 'Zonas por municipio y localidad'; Etiqueta = 'enhancement'; Cuerpo = 'Catálogo de zonas para organizar a los clientes por pueblo y filtrar el padrón que atiende cada cajero.' },
    @{ Titulo = 'Eliminar codigo de ejemplo y archivos de agentes de IA'; Etiqueta = 'chore'; Cuerpo = 'welcome.blade.php, ExampleTest, comando inspire, Vite sin uso y AGENTS.md.' }
)

# Issues que ya existen (abiertos o cerrados) para no duplicarlos si el script se repite.
$existentes = GhJson @('issue', 'list', '--state', 'all', '--limit', '300', '--json', 'number,title')
$numeros = @()
foreach ($issue in $issues) {
    $previo = $existentes | Where-Object { $_.title -eq $issue.Titulo } | Select-Object -First 1
    if ($previo) {
        $numeros += $previo.number
        Write-Host "  OK  #$($previo.number) ya existía: $($issue.Titulo)" -ForegroundColor Green
    } else {
        # gh imprime la URL del issue creado; el número es el último segmento.
        $url = gh issue create --title $issue.Titulo --label $issue.Etiqueta --body $issue.Cuerpo
        if ($LASTEXITCODE -eq 0 -and $url) {
            $numero = ("$($url | Select-Object -Last 1)").Split('/')[-1]
            $numeros += $numero
            Write-Host "  +   #$numero $($issue.Titulo)" -ForegroundColor Yellow
        } else {
            Write-Host "  !!  No se pudo crear: $($issue.Titulo)" -ForegroundColor Red
        }
    }
}

# ---------------------------------------------------------------------
# 3. Pull Request
# ---------------------------------------------------------------------
Paso '3/5 Pull Request'
git push -u origin $rama
if ($LASTEXITCODE -ne 0) { Fallar "No se pudo subir la rama $rama." }

# Si la rama ya está dentro de la principal (por ejemplo, con git_version_final.ps1 -Publicar), no hay PR que abrir.
git merge-base --is-ancestor $rama $principal
$yaIntegrada = ($LASTEXITCODE -eq 0)

if ($yaIntegrada) {
    Write-Host "  La rama ya estaba integrada en $principal; se cierran los issues con comentario." -ForegroundColor Yellow
    git push origin $principal | Out-Host
    foreach ($numero in $numeros) { gh issue close $numero --comment "Resuelto en $version." *> $null }
} else {
    # ¿Ya hay un Pull Request abierto o integrado desde esta rama?
    $prs = GhJson @('pr', 'list', '--head', $rama, '--state', 'all', '--json', 'number,state')
    $pr = $prs | Where-Object { $_.state -ne 'CLOSED' } | Select-Object -First 1

    if ($pr) {
        $numeroPr = $pr.number
        $estadoPr = $pr.state
        Write-Host "  OK  Pull Request #$($numeroPr) ya existía ($estadoPr)." -ForegroundColor Green
    } else {
        # Descripción del PR: resumen y una línea "Closes #N" por issue (GitHub los cierra al integrar).
        $cierra = ($numeros | ForEach-Object { "Closes #$_" }) -join "`n"
        $cuerpo = @"
Versión final de HydroPay (Actividad 7): API REST con Laravel Sanctum, reportes mensuales, correcciones de la revisión final, pruebas y documentación.

- Cambios: CHANGELOG.md
- Errores y correcciones: docs/DOCUMENTACION_TECNICA.md, sección 18.7
- Pruebas: php artisan test (112 pruebas Pest) y colección Postman en docs/postman

$cierra
"@
        # El cuerpo va en un archivo temporal UTF-8 para conservar saltos de línea y acentos.
        $archivo = [System.IO.Path]::GetTempFileName()
        [System.IO.File]::WriteAllText($archivo, $cuerpo)
        $urlPr = gh pr create --base $principal --head $rama --title "HydroPay $version - version final (Actividad 7)" --body-file $archivo
        $codigo = $LASTEXITCODE
        Remove-Item $archivo -ErrorAction SilentlyContinue
        if ($codigo -ne 0) { Fallar 'No se pudo crear el Pull Request.' }
        $numeroPr = ("$($urlPr | Select-Object -Last 1)").Split('/')[-1]
        $estadoPr = 'OPEN'
        Write-Host "  +   Pull Request #$($numeroPr) creado." -ForegroundColor Yellow
    }

    if ($estadoPr -eq 'OPEN') {
        # Abre el PR en el navegador para revisarlo y tomar la captura de evidencia.
        gh pr view $numeroPr --web
        $respuesta = Read-Host "  Revisa el Pull Request y toma tu captura. ¿Lo integro a $principal y publico $($version)? (s/n)"
        if ($respuesta -notmatch '^[sS]') {
            Write-Host 'Pull Request abierto. Cuando quieras integrarlo, vuelve a ejecutar este script.' -ForegroundColor Cyan
            exit 0
        }
        # Merge commit: conserva todos los commits de la rama en el historial.
        gh pr merge $numeroPr --merge --subject "merge: integrar $rama (version final HydroPay)"
        if ($LASTEXITCODE -ne 0) { Fallar 'No se pudo integrar el Pull Request. Revisa en GitHub si marca conflictos.' }
        Write-Host '  OK  Pull Request integrado; GitHub cerró los issues enlazados.' -ForegroundColor Green
    }
}

# ---------------------------------------------------------------------
# 4. Etiqueta de la versión
# ---------------------------------------------------------------------
Paso "4/5 Etiqueta $version"
# Trae a tu equipo el merge hecho en GitHub.
git switch $principal
git pull --ff-only origin $principal
if ($LASTEXITCODE -ne 0) { Fallar "No se pudo actualizar $principal desde GitHub." }

# Etiqueta anotada (solo si no existe todavía).
if (git tag --list $version) {
    Write-Host "  OK  La etiqueta $version ya existía." -ForegroundColor Green
} else {
    git tag -a $version -m "HydroPay $version - version final (Actividad 7)"
    Write-Host "  +   Etiqueta $version creada." -ForegroundColor Yellow
}
git push origin $version
if ($LASTEXITCODE -ne 0) { Fallar "No se pudo subir la etiqueta $version." }

# ---------------------------------------------------------------------
# 5. Release
# ---------------------------------------------------------------------
Paso "5/5 Release $version"
gh release view $version *> $null
if ($LASTEXITCODE -eq 0) {
    Write-Host "  OK  El Release $version ya existía." -ForegroundColor Green
} else {
    # Notas: solo la sección 1.0.0 del CHANGELOG.
    $changelog = Get-Content CHANGELOG.md -Raw -Encoding UTF8
    $seccion = [regex]::Match($changelog, '(?ms)^## \[1\.0\.0\].*?(?=^## \[|\z)').Value
    if (-not $seccion) { $seccion = $changelog }
    $notas = [System.IO.Path]::GetTempFileName()
    [System.IO.File]::WriteAllText($notas, $seccion)

    # Archivos adjuntos al Release (solo los que existan).
    $adjuntos = @(
        'docs/Documentacion_Tecnica_HydroPay_Final.docx',
        'docs/postman/HydroPay_API.postman_collection.json',
        'docs/postman/HydroPay_local.postman_environment.json'
    ) | Where-Object { Test-Path $_ }

    gh release create $version $adjuntos --title "HydroPay $version" --notes-file $notas --verify-tag
    $codigo = $LASTEXITCODE
    Remove-Item $notas -ErrorAction SilentlyContinue
    if ($codigo -ne 0) { Fallar 'No se pudo crear el Release. Créalo en GitHub: Releases > Draft a new release > tag v1.0.0.' }
    Write-Host "  +   Release $version publicado." -ForegroundColor Yellow
}

# ---------------------------------------------------------------------
# Resultado
# ---------------------------------------------------------------------
$urlRepo = gh repo view --json url --jq .url
Write-Host ''
Write-Host 'Publicado.' -ForegroundColor Green
Write-Host "  Repositorio: $urlRepo" -ForegroundColor Cyan
Write-Host "  Release:     $urlRepo/releases/tag/$version" -ForegroundColor Cyan
Write-Host '  Pega la URL del repositorio en el documento técnico (sección 19.1) y toma las capturas de docs/EVIDENCIAS.md.' -ForegroundColor Cyan
