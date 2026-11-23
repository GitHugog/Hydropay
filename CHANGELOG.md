# Cambios de HydroPay

## [1.0.0] — 2026-11-21 · Versión final (Actividad 7)

### Agregado
- **Zonas** (municipio y localidad): catálogo del administrador, zona de cada cliente, filtro y columna en el listado de clientes y endpoint `GET /api/v1/zonas`.
- **Cuota del servicio por cliente** (periodicidad mensual, bimestral o anual y cuota por periodo), asignada solo por el administrador; recibos automáticos del periodo con `GeneradorRecibosService`, el comando diario `hydropay:generar-recibos` y el botón *Generar recibos del periodo*; columna *Debe* en el listado de clientes.
- API REST `/api/v1` con **Laravel Sanctum**: login por token, habilidades por rol (`leer`, `escribir`, `eliminar`), vencimiento de 8 horas, clientes, estado de cuenta, recibos, pagos y resumen.
- Colección Postman con 37 solicitudes y pruebas automáticas del código HTTP.
- Módulo de **reportes mensuales** con rutas de parámetros opcionales (`/reportes/{anio?}/{mes?}`), exportación CSV e impresión.
- Middleware propio `VerificarRol` (`rol:admin`, `rol:admin,cajero`).
- Form Requests compartidos (`ClienteRequest`, `CobroRequest`, `PagoRequest`, `UsuarioRequest`) con limpieza de HTML previa a la validación.
- Servicios `RegistroPagoService` y `ReporteMensualService`; helpers `formato_moneda`, `nombre_mes`, `etiqueta_estado`.
- Componentes Blade (`alerta`, `estado-cobro`, `indicador`, `boton-eliminar`), layout dividido en parciales, ruta de navegación y menú desplegable para celular.
- Páginas de error 403, 404, 405, 419, 429, 500 y 503 en español.
- Traducciones completas (`lang/es/*.php`, `lang/es.json`).
- 64 pruebas nuevas (112 en total): API, reportes, errores, helpers y casos adicionales.
- Documentación final: documento técnico, manuales, API, diccionario, casos de prueba, bitácora de IA, Git/GitHub y diagramas.
- Scripts `preparar_entorno.ps1` (equipo de desarrollo) y `publicar_github.ps1` (issues, Pull Request, etiqueta y Release); ajustes y extensiones recomendadas de VS Code en `.vscode/`.

### Corregido
- Avisos falsos de "Autorización denegada" en el log (ahora solo se registran 403 reales).
- Mensajes de validación sin traducir (`validation.decimal`, `validation.integer`).
- Comando de vencidos que podía omitir registros (`lazyById`).
- Carpetas de `storage/framework` ausentes en instalaciones nuevas.
- Reporte que excluiría pagos del último día del mes.
- Pagos creados fuera del servicio que dejaban el recibo "Sin pagar".
- Tokens de API vigentes tras cambiar rol o contraseña.

### Cambiado
- **Nuevo diseño** de identidad propia: membrete azul agua con el organismo y línea del nivel del agua, pestañas visibles en lugar del menú lateral, inicio con buscador de clientes, cifras en una franja sin tarjetas, tipografías Atkinson Hyperlegible Next y Archivo, botón *Cobrar* en la ficha del cliente y pantalla de acceso en dos columnas.
- La edición de recibos pagados o cancelados se controla en `CobroPolicy` (403 con motivo) para web y API.
- Seeder con fechas de pago realistas; datos ficticios en español.
- `.env.example` documentado; `.gitignore` excluye la base local y archivos de agentes de IA.

### Eliminado
- Vista `welcome`, pruebas de ejemplo, comando `inspire`, Vite/Tailwind sin uso, `AdministradorMiddleware` (reemplazado), archivos de agentes de IA.

## [0.6.0] — 2026-11-09 · Actividad 6
- Autenticación, recuperación y cambio de contraseña, roles, middleware de administrador, Policies, Cache, Collections, Events, Notifications, File Storage, comando Artisan, logging y pruebas.

## [0.5.0] — 2026-10 · Actividad 5
- Base de datos, modelos y relaciones, CRUD de clientes y recibos, pagos completos, controladores, rutas nombradas, formularios con validación, plantilla Blade y primera versión responsiva.
