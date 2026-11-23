# Control de versiones: Git y GitHub

## 19.1 Repositorio

| Dato | Valor |
|---|---|
| URL | <https://github.com/GitHugog/Hydropay> |
| Creación | 23/11/2026 con `gh repo create` (público) desde `scripts/publicar_github.ps1` |
| Release | <https://github.com/GitHugog/Hydropay/releases/tag/v1.0.0> |
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

Historial publicado (en `main`, commit inicial vacío; en la rama de trabajo, los commits temáticos que crea `scripts/git_version_final.ps1`; la lista exacta se comprueba con `git log --oneline` y su captura va en el Anexo H):

0. `chore: inicio del repositorio de HydroPay` (en `main`)
1. `chore: eliminar codigo de ejemplo, Vite sin uso y archivos de asistentes de IA`
2. `build(deps): agregar laravel/sanctum, helpers globales y configuracion de HydroPay`
3. `fix(i18n): traducciones completas en espanol y mensajes centralizados`
4. `refactor(seguridad): middleware VerificarRol, policies con mensajes y registro de 403 reales`
5. `feat(clientes): zonas por municipio y localidad, cuota por cliente y recibos automaticos`
6. `refactor(mvc): Form Requests y servicio de cobranza compartidos por web y API`
7. `feat(reportes): reporte mensual con rutas de parametros opcionales y exportacion CSV`
8. `feat(api): API REST v1 con Laravel Sanctum, resources y coleccion Postman`
9. `feat(ui): diseno azul agua con membrete, pestanas, buscador de clientes, componentes Blade y paginas de error`
10. `chore(db): documentar migraciones, seeders y factories`
11. `test: pruebas de API, reportes, manejo de errores y helpers`
12. `docs: README, manuales, API, bitacora de IA y documento tecnico final`
13. `chore: ajustes finales de la version 1.0.0` (solo si quedaron archivos sin tema)
14. `merge: integrar feature/actividad-7-version-final (version final HydroPay)` (al integrar el Pull Request #11)

## 19.3 Ramas

| Rama | Propósito | Estado |
|---|---|---|
| `main` | Versión estable entregable | Recibe las integraciones |
| `feature/actividad-7-version-final` | Actividad 7: API, reportes, zonas, cuotas, diseño, correcciones y documentación | Integrada a `main` con el Pull Request #11 (merge commit) |

Nota: el trabajo de las actividades 5 y 6 no se había publicado en GitHub; el repositorio se creó el 23/11/2026 con la versión final, y su historial se organizó por temas para que cada commit muestre una parte del sistema.

## 19.4 Issues

Se registraron **10 issues (#1 a #10)** el 23/11/2026, que el Pull Request #11 cerró al integrarse: #1 a #4 errores (`bug`), #5 a #9 mejoras (`enhancement`) y #10 limpieza (`chore`). Los problemas se registran en GitHub (*Issues › New issue*) y se cierran al integrar. `scripts/publicar_github.ps1` los crea automáticamente (sin duplicarlos). A mano, con GitHub CLI (la etiqueta `chore` no existe por defecto y se crea primero):

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
3. En GitHub: *Pull requests › New* (base `main`), descripción con el resumen del CHANGELOG y `Closes #1 … #10` (en este proyecto: Pull Request #11).
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
