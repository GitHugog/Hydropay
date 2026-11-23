# Control de versiones: Git y GitHub

## 19.1 Repositorio

| Dato | Valor |
|---|---|
| URL | `https://github.com/[USUARIO]/HydroPay` **[Completar]** |
| Rama principal | `main` |
| Versión final | `v1.0.0` (etiqueta anotada + Release en GitHub) |
| Archivos excluidos | `.env`, `vendor/`, `node_modules/`, `database/*.sqlite`, `storage/logs`, archivos de agentes de IA (ver `.gitignore`) |

Comprobaciones de seguridad antes de publicar:

```powershell
git ls-files .env                 # No debe mostrar nada (no está versionado)
git log --all --oneline -- .env   # Si muestra commits, .env se publicó alguna vez
git check-ignore -v .env          # Debe indicar la regla de .gitignore que lo excluye
```

Si `.env` llegó a publicarse: quitarlo con `git rm --cached .env`, hacer commit y **regenerar la clave** con `php artisan key:generate` (la clave anterior debe considerarse expuesta).

## 19.2 Commits

Convención: *Conventional Commits* en español, un commit por tema (`tipo(alcance): descripción`).

| Tipo | Uso |
|---|---|
| `feat` | Funcionalidad nueva |
| `fix` | Corrección de errores |
| `refactor` | Reorganización sin cambiar el comportamiento |
| `test` | Pruebas |
| `docs` | Documentación |
| `build` | Dependencias |
| `chore` | Limpieza y mantenimiento |

Commits de la versión final (los crea `scripts/git_version_final.ps1`):

1. `chore: eliminar codigo de ejemplo, Vite sin uso y archivos de asistentes de IA`
2. `build(deps): agregar laravel/sanctum, helpers globales y configuracion de HydroPay`
3. `fix(i18n): traducciones completas en espanol y mensajes centralizados`
4. `refactor(seguridad): middleware VerificarRol, policies con mensajes y registro de 403 reales`
5. `refactor(mvc): Form Requests y servicio de cobranza compartidos por web y API`
6. `feat(reportes): reporte mensual con rutas de parametros opcionales y exportacion CSV`
7. `feat(api): API REST v1 con Laravel Sanctum, resources y coleccion Postman`
8. `feat(ui): plantilla en parciales, componentes Blade, menu movil y paginas de error`
9. `chore(db): documentar migraciones, seeders y factories`
10. `test: pruebas de API, reportes, manejo de errores y helpers`
11. `docs: README, manuales, API, bitacora de IA y documento tecnico final`

## 19.3 Ramas

| Rama | Propósito | Estado |
|---|---|---|
| `main` | Versión estable entregable | Recibe las integraciones |
| `feature/crud-cobranza` | Actividad 5: BD, CRUD, MVC | Integrada [Completar si existió] |
| `feature/seguridad` | Actividad 6: autenticación, roles, servicios | Integrada [Completar si existió] |
| `feature/actividad-7-version-final` | Actividad 7: API, reportes, correcciones, docs | Se integra con `merge --no-ff` |

## 19.4 Issues

Registrar en GitHub (*Issues › New issue*) los problemas encontrados en la revisión final y cerrarlos al integrar. `scripts/publicar_github.ps1` los crea automáticamente (sin duplicarlos). A mano, con GitHub CLI (la etiqueta `chore` no existe por defecto y se crea primero):

```powershell
gh label create chore --color BFD4F2 --description "Limpieza y mantenimiento"
gh issue create --title "Mensajes de validacion sin traducir (validation.decimal)" --label bug --body "Las reglas decimal e integer mostraban la clave en lugar del mensaje en espanol."
gh issue create --title "Log con avisos falsos de autorizacion denegada" --label bug --body "Gate::after registra cada @can de las vistas como acceso denegado (31 avisos el 17/11/2026)."
gh issue create --title "Error de cache path en instalaciones nuevas" --label bug --body "Faltan las carpetas storage/framework/cache, sessions y views en el repositorio."
gh issue create --title "Comando de vencidos puede saltar registros" --label bug --body "chunk() con where sobre la columna que se modifica omite filas; usar lazyById()."
gh issue create --title "Modulo de reportes mensuales" --label enhancement --body "Ingresos del mes, cobros pendientes, clientes y exportacion CSV (requisito original)."
gh issue create --title "API REST con autenticacion por token" --label enhancement --body "API v1 con Laravel Sanctum, habilidades por rol y coleccion Postman."
gh issue create --title "Menu inutilizable en celular y paginas de error en ingles" --label enhancement --body "Menu desplegable, componentes Blade y vistas 403/404/419/429/500/503."
gh issue create --title "Eliminar codigo de ejemplo y archivos de agentes de IA" --label chore --body "welcome.blade.php, ExampleTest, inspire, Vite sin uso, AGENTS.md."
```

Para cerrarlos desde un commit o el Pull Request, incluir `Closes #N` en la descripción.

## 19.5 Integración

Automatizado de punta a punta (pasos 19.4 a 19.6) con `.\scripts\publicar_github.ps1`: crea el repositorio si no hay remoto, registra los issues, sube las ramas, abre el Pull Request con `Closes #N`, pide confirmación antes de integrarlo (momento para tomar la captura), crea la etiqueta `v1.0.0` y el Release con el documento técnico y la colección Postman adjuntos. Pasos equivalentes a mano:


1. Trabajo en `feature/actividad-7-version-final`.
2. `git push -u origin feature/actividad-7-version-final`.
3. En GitHub: *Pull requests › New* (base `main`), descripción con el resumen del CHANGELOG y `Closes #1 … #8`.
4. Antes de integrar: `php artisan test` en verde (captura como evidencia).
5. *Merge pull request* (o localmente `git merge --no-ff`), para conservar la rama en el historial.

## 19.6 Release

```powershell
git switch main
git pull
git tag -a v1.0.0 -m "HydroPay v1.0.0 - version final (Actividad 7)"
git push origin v1.0.0
gh release create v1.0.0 --title "HydroPay v1.0.0" --notes-file CHANGELOG.md
```

Sin GitHub CLI: *Releases › Draft a new release › Choose a tag: v1.0.0 › Title: HydroPay v1.0.0* y pegar el contenido de `CHANGELOG.md`.

## 19.7 Historial

Evidencia del historial (captura de la terminal):

```powershell
git log --oneline --graph --decorate --all -n 40
git tag -n
git branch -a
```

GitHub muestra lo mismo en *Insights › Network* y en la pestaña *Commits*.
