# Guía de evidencias (capturas) — versión final

Las capturas deben salir **de tu equipo con el sistema ejecutándose** (`php artisan serve`). Guárdalas en `docs/capturas/` con el nombre indicado y pégalas en el Anexo H del documento técnico. Las imágenes de los apartados 9.6 y 17.4 del documento son prototipos renderizados de las plantillas, no sustituyen estas capturas.

| # | Evidencia mínima | Cómo obtenerla | Archivo |
|---|---|---|---|
| 1 | URL del repositorio final | Página principal del repositorio en GitHub | `01_repositorio.png` |
| 2 | Release o versión identificada | GitHub › Releases › v1.0.0 | `02_release.png` |
| 3 | README completo | README renderizado en GitHub | `03_readme.png` |
| 4 | Sistema funcionando | Terminal con `php artisan serve` + navegador en el panel | `04_sistema.png` |
| 5 | Base de datos funcional | `php artisan migrate:status` y tablas en un visor SQLite (DB Browser / TablePlus) | `05_base_datos.png` |
| 6 | CRUD completo | Alta, listado, edición y eliminación de un cliente (4 capturas o una secuencia) | `06_crud_*.png` |
| 7 | Usuarios y roles | Módulo Usuarios con los tres roles | `07_usuarios_roles.png` |
| 8 | Acceso denegado | Iniciar como cajero y abrir `/usuarios` → 403 | `08_acceso_denegado.png` |
| 9 | Pruebas realizadas | `php artisan test` con el resumen final | `09_pruebas.png` |
| 10 | Evidencia de API | Postman: login (201), listado (200), creación sin permiso (403) y Collection Runner completo | `10_api_*.png` |
| 11 | Paquete adicional funcionando | Tabla `personal_access_tokens` con tokens + `composer show laravel/sanctum` | `11_sanctum.png` |
| 12 | Historial Git | `git log --oneline --graph --decorate -n 30` | `12_historial_git.png` |
| 13 | Documento técnico final | Portada e índice del Word | `13_documento.png` |
| 14 | Manual de instalación | `docs/MANUAL_INSTALACION.md` en GitHub | `14_manual_instalacion.png` |
| 15 | Manual de usuario | `docs/MANUAL_USUARIO.md` en GitHub | `15_manual_usuario.png` |
| 16 | Bitácora de IA | `docs/BITACORA_IA.md` | `16_bitacora_ia.png` |
| 17 | Versión final ejecutándose | Pie de página con "Versión 1.0.0" y `git describe --tags` en la terminal | `17_version_final.png` |
| 18 | Diseño responsivo | Chrome › F12 › modo dispositivo (390 px) con el menú abierto | `18_responsivo.png` |
| 19 | Reporte mensual | Reportes › noviembre 2026 y el CSV abierto en Excel | `19_reporte.png` |
| 20 | Logs | `storage/logs/laravel-AAAA-MM-DD.log` con "Pago completo registrado" y "Acceso denegado (403)" | `20_logs.png` |
| 21 | `.env` fuera de GitHub | `git ls-files .env` (sin resultado) y `git check-ignore -v .env` | `21_env.png` |
| 22 | Pull Request de integración | GitHub › Pull requests › “HydroPay v1.0.0 - version final” con los commits y `Closes #N` (antes de confirmar en `publicar_github.ps1`) | `22_pull_request.png` |
| 23 | Issues cerrados | GitHub › Issues › Closed (cerrados por el Pull Request) | `23_issues.png` |

## Guion sugerido para la demostración (8-10 minutos)

1. **Contexto (1 min):** problema de cobro de agua en comunidades sin portal oficial; roles del sistema.
2. **Administrador (2 min):** panel, alta de un cajero en Usuarios, reporte mensual y CSV.
3. **Cajero (2 min):** registrar cliente → registrar recibo → registrar pago completo; intentar un abono (error) y un segundo pago (error).
4. **Consulta (1 min):** puede ver pero no registrar; abrir `/usuarios` a mano → 403.
5. **API (2 min):** Postman: login, listado, estado de cuenta, pago; token de consulta intentando crear → 403.
6. **Calidad (1 min):** `php artisan test`, celular con el menú desplegable, `git log` y el Release v1.0.0.
