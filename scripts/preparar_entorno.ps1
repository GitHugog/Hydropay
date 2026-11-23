# =====================================================================
# HYDROPAY — PREPARAR LA COMPUTADORA (una sola vez por equipo)
#
# Revisa e instala SOLO lo que falte:
#   1. winget (instalador de paquetes de Windows).
#   2. Git, Visual Studio Code, GitHub CLI y Postman.
#   3. PHP 8.4 o superior y Composer (vienen con Laravel Herd) y sus extensiones.
#   4. Extensiones de Visual Studio Code para Laravel.
#   5. Tu identidad en Git (nombre y correo) y la rama principal "main".
#   6. Inicio de sesión en GitHub (abre el navegador).
#
# Uso (PowerShell normal, desde la raíz del proyecto):
#   Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process
#   .\scripts\preparar_entorno.ps1
#
# Se puede ejecutar varias veces: lo que ya está listo solo se reporta.
# =====================================================================

# Un programa que falle no detiene el script; cada paso revisa su propio resultado.
$ErrorActionPreference = 'Continue'

# Muestra bien los acentos que imprimen git, gh y php.
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

# Lista de cosas que el script no pudo resolver solo (se muestran al final).
$pendientes = New-Object System.Collections.Generic.List[string]

# Imprime el título de cada sección.
function Titulo([string]$texto) {
    Write-Host ''
    Write-Host "== $texto ==" -ForegroundColor Cyan
}

# Devuelve $true si el comando existe en el PATH de esta terminal.
function Existe([string]$comando) {
    return [bool](Get-Command $comando -ErrorAction SilentlyContinue)
}

# Agrega al PATH de esta terminal lo que un instalador acaba de registrar en Windows
# (se agrega al final para no cambiar qué PHP se usa primero).
function RefrescarPath {
    $maquina = [Environment]::GetEnvironmentVariable('Path', 'Machine')
    $usuario = [Environment]::GetEnvironmentVariable('Path', 'User')
    $env:Path = "$env:Path;$maquina;$usuario"
}

# Instala un programa con winget cuando su comando no existe.
#   $nombre   : nombre que se muestra.
#   $idWinget : identificador exacto del paquete en winget.
#   $comando  : comando que confirma la instalación ('' si el programa no tiene comando).
function Instalar([string]$nombre, [string]$idWinget, [string]$comando) {
    # Ya instalado: solo se informa.
    if ($comando -and (Existe $comando)) {
        Write-Host "  OK  $nombre ya está instalado." -ForegroundColor Green
        return
    }
    # Sin winget no se puede instalar automáticamente.
    if (-not (Existe 'winget')) {
        $pendientes.Add("Instala $nombre a mano (no hay winget en este equipo).")
        return
    }
    # Instalación silenciosa aceptando los términos del paquete.
    Write-Host "  ... Instalando $nombre (puede pedir permiso de administrador)" -ForegroundColor Yellow
    winget install --id $idWinget -e --accept-source-agreements --accept-package-agreements
    # Hace visible el programa recién instalado en esta misma terminal.
    RefrescarPath
    # Si el comando sigue sin aparecer, hay que abrir una terminal nueva.
    if ($comando -and -not (Existe $comando)) {
        $pendientes.Add("$nombre se instaló pero esta terminal no lo ve: ciérrala, abre otra y vuelve a ejecutar el script.")
    }
}

Write-Host '=== HydroPay: preparación del equipo ===' -ForegroundColor Cyan

# ---------------------------------------------------------------------
# 1. winget
# ---------------------------------------------------------------------
Titulo '1/6 Instalador de paquetes (winget)'
if (Existe 'winget') {
    Write-Host '  OK  winget disponible.' -ForegroundColor Green
} else {
    # winget viene en Windows 10/11 con "Instalador de aplicación" de Microsoft Store.
    $pendientes.Add('Instala "Instalador de aplicación" (App Installer) desde Microsoft Store, o descarga a mano: git-scm.com, code.visualstudio.com, cli.github.com y postman.com.')
}

# ---------------------------------------------------------------------
# 2. Programas
# ---------------------------------------------------------------------
Titulo '2/6 Programas'
# Control de versiones.
Instalar 'Git' 'Git.Git' 'git'
# Editor de código.
Instalar 'Visual Studio Code' 'Microsoft.VisualStudioCode' 'code'
# Herramienta de línea de comandos de GitHub (issues, pull requests y releases).
Instalar 'GitHub CLI' 'GitHub.cli' 'gh'
# Postman no tiene comando: se busca su ejecutable en la ruta de instalación por usuario.
if (Test-Path (Join-Path $env:LOCALAPPDATA 'Postman\Postman.exe')) {
    Write-Host '  OK  Postman ya está instalado.' -ForegroundColor Green
} else {
    Instalar 'Postman' 'Postman.Postman' ''
}

# ---------------------------------------------------------------------
# 3. PHP y Composer
# ---------------------------------------------------------------------
Titulo '3/6 PHP y Composer (Laravel Herd)'
if (-not (Existe 'php')) {
    # Herd instala PHP, Composer y el instalador de Laravel.
    $pendientes.Add('No hay PHP. Instala Laravel Herd (https://herd.laravel.com/windows), ábrelo una vez y vuelve a ejecutar este script.')
} else {
    # Ruta y versión del PHP que realmente se usa.
    $rutaPhp = (Get-Command php).Source
    # Se toma la última línea por si PHP imprime avisos de arranque antes de la versión.
    $versionPhp = php -r 'echo PHP_VERSION;' | Select-Object -Last 1
    Write-Host "  PHP $versionPhp ($rutaPhp)"

    # Trampa común: el PHP 8.2 de XAMPP aparece antes que el de Herd en el PATH.
    if ($rutaPhp -match 'xampp') {
        $pendientes.Add("Se está usando el PHP de XAMPP ($rutaPhp). Quita la carpeta de PHP de XAMPP de la variable Path de Windows (Buscar > 'variables de entorno') para que se use el de Herd.")
    }

    # HydroPay requiere PHP 8.4 o superior (dependencias de composer.lock).
    # Se quitan sufijos como "RC1" antes de comparar; si no se puede leer, se trata como incompatible.
    try { $numero = [version]("$versionPhp" -replace '[^0-9.].*$', '') } catch { $numero = [version]'0.0' }
    if ($numero -lt [version]'8.4') {
        $pendientes.Add("HydroPay necesita PHP 8.4 o superior y tienes $versionPhp. En Herd: PHP > elige 8.4 como versión global.")
    } else {
        Write-Host '  OK  Versión de PHP compatible.' -ForegroundColor Green
    }

    # Extensiones que usan Laravel, SQLite y la validación de archivos.
    $modulos = php -m | ForEach-Object { $_.Trim().ToLower() }
    foreach ($extension in @('pdo_sqlite', 'mbstring', 'openssl', 'fileinfo', 'tokenizer', 'dom', 'ctype', 'curl')) {
        if ($modulos -notcontains $extension) {
            $pendientes.Add("Activa la extensión de PHP '$extension' (Herd > PHP > Extensions, o en php.ini).")
        }
    }
    Write-Host '  OK  Extensiones de PHP revisadas.' -ForegroundColor Green
}

# Composer instala las dependencias del proyecto.
if (Existe 'composer') {
    Write-Host '  OK  Composer disponible.' -ForegroundColor Green
} else {
    $pendientes.Add('No hay Composer. Viene con Laravel Herd; con otro PHP instálalo desde getcomposer.org.')
}

# ---------------------------------------------------------------------
# 4. Extensiones de Visual Studio Code
# ---------------------------------------------------------------------
Titulo '4/6 Extensiones de Visual Studio Code'
if (Existe 'code') {
    # Extensiones ya instaladas (en minúsculas para comparar).
    $instaladas = code --list-extensions | ForEach-Object { $_.Trim().ToLower() }

    # Identificador de cada extensión y para qué sirve en HydroPay.
    $extensiones = [ordered]@{
        'laravel.vscode-laravel'              = 'Laravel (oficial): rutas, vistas, Blade y Artisan'
        'bmewburn.vscode-intelephense-client' = 'PHP Intelephense: autocompletado y errores de PHP'
        'm1guelpf.better-pest'                = 'Better Pest: ejecutar pruebas Pest desde el editor'
        'mikestead.dotenv'                    = 'DotENV: resaltado del archivo .env'
        'editorconfig.editorconfig'           = 'EditorConfig: respeta el .editorconfig del proyecto'
        'qwtel.sqlite-viewer'                 = 'SQLite Viewer: ver database.sqlite'
        'bierner.markdown-mermaid'            = 'Diagramas Mermaid en la vista previa de Markdown'
        'eamodio.gitlens'                     = 'GitLens: historial y autor de cada línea'
        'github.vscode-pull-request-github'   = 'GitHub: pull requests e issues dentro del editor'
    }

    foreach ($id in $extensiones.Keys) {
        if ($instaladas -contains $id) {
            Write-Host "  OK  $($extensiones[$id])" -ForegroundColor Green
        } else {
            code --install-extension $id --force | Out-Null
            if ($LASTEXITCODE -eq 0) {
                Write-Host "  +   $($extensiones[$id])" -ForegroundColor Yellow
            } else {
                $pendientes.Add("No se pudo instalar la extensión $id; búscala en VS Code (Ctrl+Shift+X).")
            }
        }
    }
} else {
    $pendientes.Add('Visual Studio Code no está disponible en esta terminal; ábrela de nuevo y repite el script para instalar las extensiones.')
}

# ---------------------------------------------------------------------
# 5. Identidad de Git
# ---------------------------------------------------------------------
Titulo '5/6 Identidad de Git'
if (Existe 'git') {
    # Nombre que aparecerá como autor de los commits.
    $nombre = git config --global user.name
    if (-not $nombre) {
        $nombre = Read-Host '  Tu nombre completo (aparecerá en los commits)'
        git config --global user.name $nombre
    }
    # Correo: debe ser el de tu cuenta de GitHub para que los commits se vinculen a tu perfil.
    $correo = git config --global user.email
    if (-not $correo) {
        $correo = Read-Host '  Tu correo (el mismo de tu cuenta de GitHub)'
        git config --global user.email $correo
    }
    # Los repositorios nuevos empiezan en la rama main.
    git config --global init.defaultBranch main
    Write-Host "  OK  Los commits se firmarán como: $nombre <$correo>" -ForegroundColor Green
} else {
    $pendientes.Add('Git no está disponible en esta terminal; ábrela de nuevo y repite el script.')
}

# ---------------------------------------------------------------------
# 6. Cuenta de GitHub
# ---------------------------------------------------------------------
Titulo '6/6 Cuenta de GitHub'
if (Existe 'gh') {
    # Código de salida distinto de 0 = no hay sesión iniciada.
    gh auth status *> $null
    if ($LASTEXITCODE -ne 0) {
        Write-Host '  Se abrirá el navegador: copia el código de 8 caracteres que aparece aquí y autoriza GitHub CLI.' -ForegroundColor Yellow
        gh auth login --hostname github.com --git-protocol https --web
    }
    # Git usará la misma sesión para push y pull (sin pedir contraseña).
    gh auth setup-git *> $null
    # Confirma con qué usuario quedó la sesión.
    $usuarioGitHub = gh api user --jq .login
    if ($usuarioGitHub) {
        Write-Host "  OK  Sesión de GitHub: $usuarioGitHub" -ForegroundColor Green
    } else {
        $pendientes.Add('No se pudo iniciar sesión en GitHub. Ejecuta: gh auth login --web')
    }
} else {
    $pendientes.Add('GitHub CLI no está disponible en esta terminal; ábrela de nuevo y repite el script.')
}

# ---------------------------------------------------------------------
# Resumen
# ---------------------------------------------------------------------
Titulo 'Resumen'
if ($pendientes.Count -eq 0) {
    Write-Host 'Equipo listo. Siguiente paso: .\instalar_hydropay.ps1' -ForegroundColor Green
} else {
    Write-Host 'Falta hacer esto a mano (después vuelve a ejecutar el script):' -ForegroundColor Yellow
    foreach ($pendiente in $pendientes) { Write-Host "  - $pendiente" -ForegroundColor Yellow }
}
