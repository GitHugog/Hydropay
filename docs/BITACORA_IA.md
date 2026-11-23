# Bitácora del uso de Inteligencia Artificial Generativa

Este documento registra con transparencia cómo se usó IA generativa en HydroPay, qué produjo, qué se corrigió o descartó y cómo se validó. Las celdas marcadas con **[Completar]** solo las puede llenar el estudiante, porque describen decisiones o sesiones que no quedaron registradas en el repositorio.

## 1. Herramientas

| Herramienta | Tipo | Evidencia en el proyecto | Uso principal |
|---|---|---|---|
| Claude (Anthropic) | Asistente conversacional con ejecución de código | Paquetes de las actividades 6 (09/11/2026) y 7 (21/11/2026) | Generación y revisión de código Laravel, pruebas, documentación |
| Laravel Boost + agente Codex (OpenAI) | Guías y servidor MCP para agentes de código | `boost.json` (`"agents": ["codex"]`), `.codex/config.toml`, `AGENTS.md` (se regeneran y ya no se versionan) | Guías de buenas prácticas de Laravel dentro del editor. [Completar: tareas concretas en que se usó] |
| [Completar si aplica: ChatGPT, Copilot, Gemini…] | | | |

## 2. Propósitos

1. Acelerar la escritura de código repetitivo (migraciones, CRUD, vistas, pruebas).
2. Revisar el proyecto contra los requisitos de las actividades 5, 6 y 7 e identificar faltantes.
3. Detectar errores (revisión del código y del log real `storage/logs/laravel.log`).
4. Redactar documentación técnica, manuales y comentarios del código en español.
5. Explicar conceptos de Laravel (Policies, Form Requests, Sanctum, Resources) para poder defenderlos en la demostración.

## 3. Bitácora de prompts

| # | Fecha | Herramienta | Actividad | Prompt (resumen fiel) | Resultado obtenido |
|---|---|---|---|---|---|
| 1 | [Completar] | Laravel Boost / Codex | Configuración del entorno | Instalación de Boost con frontend Blade y agente Codex (`php artisan boost:install`) | Guías `AGENTS.md`, skills y servidor MCP |
| 2 | 09/11/2026 | Claude | Actividad 6 | "Terminar la actividad de autenticación, roles y permisos, middleware, recuperación de contraseña, servicios Laravel, logging y pruebas, con ajustes a las secciones 13.x y 18.x" | Login/logout, roles, `AdministradorMiddleware`, Policies, recuperación de contraseña, Cache, Collections, Events, Notifications, File Storage, comando Artisan, pruebas y documentación |
| 3 | 21/11/2026 | Claude | Actividad 7 (versión final) | "Partiendo del proyecto (RAR adjunto) y de los requisitos de las actividades 5, 6 y 7: revisar su propósito, terminarlo, poner comentarios a todas las líneas de código y decir cómo está el proyecto" | Diagnóstico, corrección de errores, API con Sanctum, reportes, rediseño responsivo, pruebas nuevas, documentación final, colección Postman y scripts de instalación y Git |
| 4 | 21/11/2026 | Claude | Actividad 7 (entorno y GitHub) | "Dime cómo configurarlo con Visual Studio Code, Laravel y GitHub para que quede al 100 en mi compu" | Scripts `preparar_entorno.ps1` y `publicar_github.ps1`, carpeta `.vscode/`, pasos de instalación; se corrigió la guía de issues (la etiqueta `chore` no existe por defecto en GitHub) |
| 5 | 22/11/2026 | Claude | Actividad 7 (mejoras del estudiante) | "Que el administrador asigne si es anual, bimestral o mensual y cuánto debe… y espacios para poner los pueblos o el municipio" | Cuota y periodicidad por cliente con recibos automáticos y catálogo de zonas; el estudiante definió el flujo (administrador en la presidencia asigna la cuota; cajero cobra en la localidad) |
| 6 | 23/11/2026 | Claude | Actividad 7 (diseño) | "El diseño tuyo y de ChatGPT tienen un mismo diseño, que no parezca que lo hizo una IA; lo quiero fácil de usar, limpio y con buen diseño" | Rediseño completo de la interfaz (sección 17.1): identidad verde agave, pestañas, buscador primero, tipografía para baja visión; se eliminaron las etiquetas en mayúsculas, las flechas y las tarjetas repetidas |
| 7 | 23/11/2026 | Claude | Actividad 7 (diseño) | "Pero un color azul de agua, ¿por qué verde?" | Decisión del estudiante: identidad azul agua profundo `#0F4C81` con línea azul claro del nivel del agua en lugar del verde agave propuesto |
| 8 | 23/11/2026 | Claude | Actividad 7 (documentación) | "¿Qué es Postman? Y lo de la reflexión, escríbelo como piensas que lo haría yo" | Explicación de Postman y borrador en primera persona de las secciones 7 (Aprendizajes) y 8 (Ética y autoría), basado en mis decisiones y en lo que pasó al instalar y publicar; lo revisé antes de entregar |
| 9 | [Completar] | | | | |

## 4. Sugerencias de la IA y decisión tomada

| Sugerencia | Alternativas consideradas | Decisión y motivo |
|---|---|---|
| Paquete adicional: **Laravel Sanctum** | Laravel Passport (OAuth2 completo), DomPDF (PDF), Spatie Permission (roles) | Sanctum: es oficial de Laravel, resuelve la API con tokens por rol sin la complejidad de OAuth2. Spatie no aporta frente a 3 roles fijos. PDF se cubre con impresión del navegador |
| API propia en lugar de consumir un servicio externo | Mercado Pago sandbox (idea original del proyecto) | API propia: no depende de credenciales externas ni de internet para la demostración |
| Módulo de Reportes | Dejarlo fuera | Se implementó porque era un requisito original del proyecto (Inicio, Usuarios, Cobros y Reportes) |
| Reemplazar `AdministradorMiddleware` por `VerificarRol` con parámetros | Mantener solo el middleware de administrador | Un middleware con parámetros (`rol:admin`, `rol:admin,cajero`) cubre ambos casos y se ve en `route:list` |
| Form Requests compartidos por web y API | Validar en cada controlador | Evita duplicar reglas entre la interfaz web y la API |
| Pagos inmutables (sin editar ni eliminar) | CRUD completo de pagos | Se conserva el historial financiero; la reversión auditada queda como mejora futura |

## 5. Elementos generados con IA que se modificaron, corrigieron o descartaron

La versión de la actividad 6 también se generó con apoyo de IA. En la revisión final se encontraron y corrigieron estos puntos (el detalle técnico está en la sección 18.7 del documento técnico):

| Elemento generado por IA (versión anterior) | Acción | Motivo / evidencia | Revisado y probado por el estudiante |
|---|---|---|---|
| `Gate::after` que registraba toda autorización negada | **Descartado** | Llenó el log con 31 avisos falsos el 17/11/2026 (cada `@can` de las vistas contaba como "denegado") | ☐ |
| `lang/es/validation.php` con solo 20 reglas | **Corregido** (archivo completo) | Las reglas `decimal` e `integer` mostraban la clave `validation.decimal` | ☐ |
| `AdministradorMiddleware` | **Modificado** → `VerificarRol` | Se necesitaba restringir también por rol cajero | ☐ |
| Validación dentro de cada controlador y método `limpiar()` | **Modificado** → Form Requests | La API necesitaba las mismas reglas | ☐ |
| Lógica del pago dentro del controlador | **Modificado** → `RegistroPagoService` | Reutilización en la API | ☐ |
| Comando de vencidos con `chunk()` implícito | **Corregido** → `lazyById()` | Al cambiar el estado durante el recorrido se podían saltar registros | ☐ |
| Edición de recibo pagado resuelta con redirección en el controlador | **Modificado** → regla en `CobroPolicy` | Debe aplicarse igual en web y API | ☐ |
| Vista 403 que dependía de la sesión | **Corregido** → `layouts/error` | Fallaría si el usuario no estuviera autenticado | ☐ |
| Menú lateral en celular (ocupaba toda la pantalla) | **Corregido** → menú desplegable | Revisión de diseño responsivo | ☐ |
| Vista `welcome`, `ExampleTest`, comando `inspire`, Vite/Tailwind sin uso, archivos de agentes de IA | **Descartados** | No forman parte del sistema final | ☐ |
| Seeder con todos los pagos fechados "hoy" | **Corregido** | Los reportes mensuales no tenían datos realistas | ☐ |
| Recibos capturados a mano para cada cliente | **Modificado por mí** → cuota y periodicidad (mensual, bimestral o anual) por cliente, asignadas solo por el administrador, con recibos automáticos | Así trabaja un organismo: en la presidencia se fija la cuota y el cajero cobra en la localidad | ☐ |
| Clientes sin ubicación | **Modificado por mí** → zonas por municipio y localidad, con filtro en el listado | Organizar el padrón por pueblo y que cada cajero vea a sus clientes | ☐ |
| Diseño con menú lateral oscuro, tarjetas iguales y etiquetas en mayúsculas | **Descartado por mí** | Se veía como plantilla hecha por IA; pedí algo limpio y fácil para el cajero | ☐ |
| Color verde agave que propuso la IA para el rediseño | **Descartado por mí** → azul agua `#0F4C81` | El sistema es de agua y el azul se reconoce de inmediato | ☐ |
| Edición equivocada en `ApiCobrosPagosTest.php` al corregir una prueba | **Revertido por mí** con Ctrl+Z | Pegué el cambio en el archivo que no era; las pruebas lo detectaron (ParseError) | ☐ |

## 6. Validación del código generado

| Técnica | Qué se verificó | Resultado |
|---|---|---|
| `php -l` sobre todos los archivos PHP | Sintaxis | Sin errores (21/11/2026) |
| Compilación de las 42 vistas Blade con el compilador de Laravel | Sintaxis Blade y componentes | Sin errores (21/11/2026) |
| Arnés de ejecución con Eloquent + SQLite en memoria | Migraciones, seeder, policies, servicio de pagos, eventos, caché, reportes, scopes, traducciones y renderizado de todas las vistas con los 3 roles | 117 comprobaciones correctas (21/11/2026) |
| Cruce de nombres de ruta | Cada `route('…')` usado existe | Sin faltantes |
| `php artisan test` en el equipo del estudiante (PHP 8.4) | Suite Pest completa (112 pruebas) | **112 aprobadas, 399 aserciones** (23/11/2026, PHP 8.4.25 con Herd). Antes, el 22/11, 1 de 100 falló (error 19 de la sección 18.7) y se corrigió |
| Colección Postman (Collection Runner) | 37 solicitudes con validación automática del código HTTP | **46 de 46 pruebas aprobadas, 0 fallidas** (23/11/2026, entorno *HydroPay local*) |
| Prueba manual en navegador y modo celular | Navegación, formularios, mensajes | [Completar] |

> La suite Pest no pudo ejecutarse en el entorno de la IA porque ese entorno tenía PHP 8.3 y las dependencias bloqueadas requieren PHP 8.4. Por eso la ejecución en el equipo del estudiante es obligatoria antes de entregar.

## 7. Aprendizajes

- **El entorno importa tanto como el código.** Tenía XAMPP con PHP 8.2 y Herd con PHP 8.4; aprendí a revisar con `php -v` y `where.exe php` cuál se está usando, porque este proyecto solo funciona con 8.4. También aprendí a instalar herramientas desde la terminal con `winget` (así instalé GitHub CLI) y a conectar la terminal con mi cuenta de GitHub.
- **Las pruebas automáticas sí sirven.** En mi computadora, 1 de 100 pruebas falló aunque el código parecía correcto (en Laravel 13 cambió la forma de leer los errores de la sesión). Sin las pruebas no me habría dado cuenta. Al final quedaron 112 pruebas en verde.
- **Leer el error completo antes de entrar en pánico.** El instalador decía "revisa tu conexión a internet", pero el problema real era otro paquete (`boost:update`) que corría antes de existir el `.env`. También me pasó dos veces que el proyecto quedó en una carpeta dentro de otra, y con `dir` y `Test-Path artisan` encontré dónde estaba.
- **Git y GitHub como se usan en un equipo de trabajo.** Commits separados por tema, una rama para la versión final, issues para cada problema, un Pull Request que los cierra con `Closes #N`, una etiqueta `v1.0.0` y un Release. Antes solo había subido proyectos sencillos a GitHub.
- **El `.env` nunca se sube.** Guarda la clave de la aplicación; lo comprobé con `git check-ignore .env` antes de publicar.
- **La seguridad va por capas.** El middleware revisa el rol antes de entrar a la ruta, la Policy decide sobre un registro en particular (por ejemplo, un recibo pagado ya no se edita) y el Form Request valida los datos. Lo vi funcionando cuando el cajero intenta mandar una cuota y el sistema la rechaza aunque mande el dato.
- **El diseño también es parte de la solución.** Pensar en quién lo usa (un cajero en la localidad, a veces desde el celular) cambió el orden de la pantalla de inicio: primero buscar al cliente y después lo demás.

## 8. Ética y autoría

**Declaración del estudiante.** En este proyecto usé Claude (Anthropic) como herramienta de desarrollo: con mis indicaciones generó y revisó código, pruebas y documentación, y yo dirigí el trabajo, tomé las decisiones y validé el resultado en mi equipo. Lo que definí y realicé:

- **La idea y el problema:** un sistema para cobrar el agua en viviendas y localidades retiradas de la cabecera municipal, que no tienen un portal de gobierno para eso.
- **Las reglas del negocio:** el administrador, desde la presidencia, asigna la zona, la periodicidad y la cuota; el cajero cobra en la localidad; los recibos se pagan completos y sin abonos.
- **El diseño:** descarté la primera propuesta porque se veía genérica, pedí una interfaz limpia pensada para el cajero y elegí el azul de agua.
- **La puesta en marcha:** configuré el entorno en mi computadora (Herd, PHP 8.4, Composer, Git y GitHub CLI), ejecuté las 112 pruebas, detecté y resolví los errores que salieron al instalar y publiqué el proyecto en GitHub con issues, Pull Request y Release.

Soy responsable de lo entregado y puedo explicar cada parte en la demostración. Para redactar esta bitácora también me apoyé en la herramienta.

Además:

- La IA se usó como **asistente**. El estudiante es responsable del contenido entregado, de entender cada parte y de poder explicarla en la demostración.
- Todo el código generado se revisó, se comentó línea por línea en español y se validó con pruebas automatizadas; no se presentan resultados de pruebas que no se hayan ejecutado.
- No se compartieron con la IA datos personales reales: los clientes, usuarios y pagos son ficticios (Faker). El archivo `.env` con la clave real no forma parte de la entrega.
- Los paquetes usados (Laravel, Sanctum, Pest) son de código abierto con licencia MIT y se citan en las referencias.
- Este documento declara el uso de IA de forma explícita, como lo pide la actividad.
