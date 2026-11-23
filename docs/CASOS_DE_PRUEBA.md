# Casos de prueba de HydroPay 1.0.0

Cada caso cubre al menos una de las condiciones pedidas: **datos válidos (V)**, **datos inválidos (I)**, **campos vacíos (E)**, **usuario autorizado (A)** y **usuario no autorizado (N)**. La columna *Prueba automatizada* indica la prueba Pest que lo verifica; la columna *Manual* indica dónde repetirlo en el navegador o en Postman.

Ejecutar todas las pruebas:

```powershell
php artisan test                    # suite completa
php artisan test --filter=Api       # solo API
php artisan test tests/Feature/CobroPagoTest.php
```

> Registrar en la última columna el resultado obtenido en tu equipo (✔/✘) y guardar la captura de la terminal como evidencia. Este documento no incluye resultados inventados.

## 1. Autenticación (web)

| ID | Tipo | Caso | Datos | Resultado esperado | Prueba automatizada | Manual | Resultado |
|---|---|---|---|---|---|---|---|
| AU-01 | N | Abrir el panel sin sesión | GET `/dashboard` | Redirección a `/login` | `AutenticacionTest` › un visitante sin sesión… | Navegador en ventana privada | |
| AU-02 | V | Login correcto | cajero / contraseña válida | Panel principal, sesión iniciada | …inicia sesión con credenciales correctas | Login | |
| AU-03 | I | Contraseña incorrecta | `incorrecta` | Error "Las credenciales no coinciden…" | …falla con contraseña incorrecta | Login | |
| AU-04 | E | Campos vacíos | correo y contraseña vacíos | Errores en ambos campos | …login con campos vacíos… | Login | |
| AU-05 | I | Fuerza bruta | 5 intentos fallidos + 1 correcto | Bloqueo temporal con segundos restantes | …se bloquea después de cinco intentos | Login | |
| AU-06 | V | Cerrar sesión | POST `/logout` | Vuelve al login con mensaje | …cierra sesión | Menú › Cerrar sesión | |
| AU-07 | N | Auto-registro público | GET/POST `/registro` | 404 (solo el admin crea cuentas) | …registro público está deshabilitado | URL manual | |
| AU-08 | V | Recuperación: enviar enlace | correo registrado | Notificación `ResetPassword` enviada; mensaje genérico | …se envía el enlace de recuperación | `storage/logs` | |
| AU-09 | V | Restablecer con token válido | token + nueva contraseña | Contraseña cambiada (hash verificado) | …con un token válido se restablece | Enlace del log | |
| AU-10 | I | Token falso | `token-falso` | Error "El enlace… no es válido" | …un token inválido no restablece | — | |
| AU-11 | V | Cambiar contraseña con sesión | actual correcta + nueva válida | Mensaje de éxito | …usuario autenticado cambia su contraseña | Menú › Cambiar contraseña | |
| AU-12 | I | Contraseña actual incorrecta | actual errónea | Error en `password_actual` | …exige la contraseña actual correcta | Igual | |
| AU-13 | I | Contraseña débil | `corta` | Error de política (8+, letras y números) | …debe tener letras, números… | Igual | |

## 2. Autorización, roles y middleware

| ID | Tipo | Caso | Resultado esperado | Prueba automatizada | Resultado |
|---|---|---|---|---|---|
| AZ-01 | A | Admin abre Usuarios | 200 | `AutorizacionTest` › el administrador entra… | |
| AZ-02 | N | Cajero escribe `/usuarios` | 403 (middleware `rol:admin`) + registro en log | …cajero recibe 403… / `ErroresTest` › se registra en el log | |
| AZ-03 | N | Consulta escribe `/usuarios` | 403 | …usuario de consulta recibe 403… | |
| AZ-04 | A/N | Consulta ve clientes pero no crea | 200 en listado; 403 en create/store | …puede ver clientes pero no crearlos | |
| AZ-05 | N | Consulta registra recibo o pago | 403 | …no puede registrar recibos ni pagos | |
| AZ-06 | A | Cajero registra y edita cliente | Redirección con mensaje | …el cajero registra y edita clientes | |
| AZ-07 | N/A | Cajero elimina cliente / admin elimina | 403 / eliminado | …no puede eliminar clientes (403) pero el administrador sí | |
| AZ-08 | N | Editar recibo pagado por URL | 403 "ya fue liquidado" (CobroPolicy) | …recibo pagado no se puede editar… | |
| AZ-09 | N | Cajero cancela recibo / admin cancela | 403 / cancelado | `CobroPagoTest` › solo el administrador puede cancelar | |
| AZ-10 | N | Admin se quita su rol | Rol se conserva | …no puede quitarse su propio rol | |
| AZ-11 | N | Admin elimina su cuenta | Mensaje de error, cuenta intacta | …no puede eliminar su propia cuenta | |
| AZ-12 | V | Alta de usuario + hash | Rol asignado; contraseña con hash; correo en minúsculas | …registra usuarios y las contraseñas se guardan con hash | |
| AZ-13 | I | Rol inexistente y contraseña débil | Errores `rol` y `password` | …rechaza un rol inexistente… | |

## 3. Clientes (CRUD, validación, relaciones)

| ID | Tipo | Caso | Resultado esperado | Prueba automatizada | Resultado |
|---|---|---|---|---|---|
| CL-01 | E | Guardar sin datos | Errores en nombre, cuenta y dirección | `ClienteCrudTest` › valida los campos obligatorios | |
| CL-02 | I | Correo inválido, teléfono con letras, cuenta duplicada | Tres errores | …valida el formato del correo, el teléfono… | |
| CL-03 | I | Validación fallida conserva datos | `old('nombre')` conservado | …conserva los datos capturados | |
| CL-04 | V | Crear, consultar, editar y eliminar | Operaciones con mensajes de éxito | …registra, consulta, modifica y elimina | |
| CL-05 | I | Eliminar cliente con recibos | Mensaje de error; no se borra | …no elimina un cliente que tiene cobros | |
| CL-06 | V | Búsqueda y paginación | Encuentra por nombre; 10 por página | …la búsqueda filtra… | |
| CL-07 | V | Filtro de inactivos | Solo inactivos | …filtro de estado… | |
| CL-08 | I | HTML/JS en el nombre | Etiquetas eliminadas | …se sanitiza antes de guardarse | |
| CL-09 | V | Adeudo en el detalle (relación cliente→recibos) | Muestra `$250.00` | …muestra su adeudo actual | |
| CU-01 | V | Periodo según periodicidad | Mensual "Noviembre 2026" (vence 30/11); bimestral "Noviembre-Diciembre 2026" (31/12); "Enero-Febrero 2027" (28/02); anual "Anual 2026" (31/12) | `CuotaRecibosTest` › calcula la etiqueta y la fecha límite | |
| CU-02 | V | Administrador registra cliente con cuota mensual de $150 | Se crea el recibo "Noviembre 2026" por $150; el listado muestra Debe $150.00 | …registra un cliente con cuota y se genera su recibo | |
| CU-03 | E | Cliente con cuota vacía | No se crean recibos | …un cliente sin cuota no genera recibos | |
| CU-04 | A | Cajero envía periodicidad y cuota | Errores "Solo el administrador puede asignar…"; no se guarda | …el cajero no puede asignar ni cambiar la cuota | |
| CU-05 | V | Comando ejecutado dos veces y luego en diciembre | 3 recibos (mensual, bimestral, anual) sin duplicar; en diciembre solo se agrega "Diciembre 2026" | …el comando genera un recibo por periodo sin duplicarlo | |
| CU-06 | A | Botón *Generar recibos del periodo* | Cajero 403; administrador genera 1 recibo con mensaje | …solo el administrador usa el botón de generar recibos | |
| ZN-01 | V | Administrador registra, edita y elimina la zona *Tala › Cuisillos* | Mensajes de éxito; aparece y desaparece del listado | `ZonaTest` › el administrador registra, edita y elimina una zona | |
| ZN-02 | E/I | Zona con campos vacíos; localidad repetida en el mismo municipio; misma localidad en otro municipio | Errores en municipio y localidad; "Esa localidad ya está registrada en ese municipio."; el tercero se guarda | …valida campos vacíos y no repite una localidad | |
| ZN-03 | A | Cajero abre o envía el catálogo de zonas | 403 | …el cajero recibe 403 en el catálogo de zonas | |
| ZN-04 | I | Eliminar zona con clientes | Mensaje de error; no se borra | …no elimina una zona que tiene clientes asignados | |
| ZN-05 | V/I | Cliente con zona; filtro del listado; zona inexistente (9999) | El filtro muestra solo esa zona; error en zona_id | …asigna zona al cliente y filtra el listado por zona | |
| ZN-06 | V | API `GET /zonas`, cliente con zona y `?zona_id=` | JSON con localidad, municipio y número de clientes | …la API lista las zonas y devuelve la zona de cada cliente | |

## 4. Recibos y pagos (reglas de negocio)

| ID | Tipo | Caso | Resultado esperado | Prueba automatizada | Resultado |
|---|---|---|---|---|---|
| CP-01 | V/I | Recibo nuevo y periodo repetido | Creado / error en periodo | `CobroPagoTest` › registra un recibo y evita repetir… | |
| CP-02 | I | Recibo para cliente inactivo | Error en cliente | …no permite emitir recibos a un cliente inactivo | |
| CP-03 | V | Fecha límite pasada | Nace vencido | …se registra como vencido | |
| CP-04 | I | Monto negativo o con 3 decimales | Error en monto | …importe negativo o con más de dos decimales | |
| CP-05 | I | Mensajes traducidos | Sin claves `validation.*` | …mensajes de validación aparecen en español | |
| CP-06 | V | Pago completo | Recibo pagado, saldo 0, un pago | …un pago registra exactamente el importe… | |
| CP-07 | I | Abono (monto menor) | Error en monto; sin pago | …rechaza un intento de pago parcial | |
| CP-08 | I | Monto mayor | Error en monto | …rechaza un importe… mayor o diferente | |
| CP-09 | I | Segundo pago | Error en recibo; un solo pago | …no permite registrar un segundo pago | |
| CP-10 | I | Duplicado directo en BD | `QueryException` (índice único) | …la base de datos impide un segundo pago | |
| CP-11 | I | Pagar recibo cancelado | Error en recibo | …un recibo cancelado no admite pagos | |
| CP-12 | I | Método inválido y fecha futura | Errores en método y fecha | …fecha futura o método inválido | |
| CP-13 | V | Notificación al cliente | Correo `PagoRecibido` enviado | …dispara la notificación por correo | |
| CP-14 | I/V | Comprobante `.exe` / `.pdf` | Rechazado / guardado en disco privado y descargable | …guarda el comprobante en almacenamiento privado | |
| CP-15 | I | Eliminar recibo pagado | Mensaje de error | …un recibo con pago no se puede eliminar | |
| CP-16 | N | DELETE de un pago | 405 | …los pagos no tienen una ruta de eliminación | |
| CP-17 | V | Comando de vencidos | Atrasado → vencido; vigente sin cambio | …el comando artisan marca como vencidos | |
| CP-18 | V | Panel principal | Muestra indicadores | …el panel principal muestra el resumen | |

## 5. Reportes

| ID | Tipo | Caso | Resultado esperado | Prueba automatizada | Resultado |
|---|---|---|---|---|---|
| RE-01 | N | Reportes sin sesión | Redirección al login | `ReporteTest` › un visitante sin sesión… | |
| RE-02 | V | `/reportes` sin parámetros | Mes actual | …sin parámetros muestra el reporte del mes actual | |
| RE-03 | V | `/reportes/2026/11` | Solo pagos de noviembre (incluye el día 30) | …calcula los ingresos solo de ese mes | |
| RE-04 | I | `/reportes/2026/13` | 404 | …un mes fuera de rango responde 404 | |
| RE-05 | I | `/reportes/abc` | 404 (`whereNumber`) | …un parámetro no numérico responde 404 | |
| RE-06 | V | Selector `?periodo=2026-10` | Redirección a `/reportes/2026/10` | …el selector de mes redirige… | |
| RE-07 | V | Deudores | Lista clientes con adeudo vencido | …lista a los clientes con adeudo vencido | |
| RE-08 | V | Exportar CSV | `text/csv` con encabezados y pagos | …exporta el detalle de pagos del mes en CSV | |

## 6. Manejo de errores y logging

| ID | Tipo | Caso | Resultado esperado | Prueba automatizada | Resultado |
|---|---|---|---|---|---|
| ER-01 | I | Ruta inexistente | Página 404 en español | `ErroresTest` › una dirección inexistente… | |
| ER-02 | I | Cliente inexistente | 404 | …un cliente inexistente responde 404 | |
| ER-03 | N | Acceso denegado | Página 403 con motivo | …el acceso denegado muestra la página 403… | |
| ER-04 | N | Registro de 403 real | `Acceso denegado (403)` en el log | …un acceso denegado real se registra en el log | |
| ER-05 | V | Listado con botones ocultos por `@can` | Sin avisos falsos en el log | …no genera avisos en el log | |

## 7. API REST (Pest + Postman)

| ID | Tipo | Caso | Resultado esperado | Prueba automatizada | Postman |
|---|---|---|---|---|---|
| AP-01 | V | Login cajero | 201, token, habilidades `leer,escribir` | `ApiAutenticacionTest` › entrega un token… | 1. Login cajero |
| AP-02 | V | Login consulta | Habilidad `leer` | …solo tiene la habilidad leer | 1. Login consulta |
| AP-03 | I | Contraseña incorrecta | 401 | …rechaza credenciales incorrectas | Login 401 |
| AP-04 | E | Login vacío | 422 `email`, `password` | …con campos vacíos responde 422 | Login 422 |
| AP-05 | N | Sin token | 401 en español | …sin token responde 401 | 5. Sin token |
| AP-06 | V | `GET /auth/yo` | Usuario y habilidades | …el endpoint yo… | Perfil del token |
| AP-07 | V | Logout revoca el token | 200 y luego 401 | …el logout de la API revoca el token | 6. Logout / Token revocado |
| AP-08 | I | Token vencido | 401 | …un token vencido responde 401 | — |
| AP-09 | V | Listar clientes | 200 con `data`, `links`, `meta` | `ApiClientesTest` › lista clientes paginados | 2. Listar |
| AP-10 | V | Buscar clientes | 1 resultado | …filtra clientes por texto | 2. Buscar |
| AP-11 | N | Token sin `escribir` crea | 403 "no tiene la habilidad" | …sin la habilidad escribir… | 5. Consulta intenta crear |
| AP-12 | N | Rol consulta con habilidad forzada | 403 por Policy | …la Policy bloquea al rol consulta… | — |
| AP-13 | V | Crear cliente | 201 | …registra un cliente por la API | 2. Crear cliente |
| AP-14 | E | Crear vacío | 422 | …con campos vacíos responde 422 | — |
| AP-15 | I | Correo inválido y cuenta duplicada | 422 | …rechaza correo inválido y cuenta duplicada | 2. Crear inválido |
| AP-16 | V | Ver y actualizar | 200 | …muestra y actualiza un cliente | 2. Ver / Actualizar |
| AP-17 | I | Cliente inexistente | 404 en español | …inexistente responde 404 | 2. Inexistente |
| AP-18 | N | Cajero elimina | 403 | …el cajero no puede eliminar… | 5. Cajero elimina |
| AP-19 | A | Admin elimina con/sin recibos | 409 / 200 | …elimina solo clientes sin recibos | 5. Admin elimina (409) |
| AP-20 | V | Estado de cuenta | Suma solo recibos por pagar | …el estado de cuenta suma… | 2. Estado de cuenta |
| AP-21 | V/I | Recibo nuevo / periodo repetido | 201 / 422 | `ApiCobrosPagosTest` › registra un recibo… | 3. Crear / repetido |
| AP-22 | V | Filtrar vencidos | Solo vencidos | …filtra recibos por estado | 3. Filtrar vencidos |
| AP-23 | V | Pago completo | 201, recibo pagado | …registra un pago completo… | 4. Registrar pago |
| AP-24 | I | Abono | 422 `monto` | …rechaza abonos con 422 | 4. Pago parcial |
| AP-25 | I | Segundo pago | 422 `cobro_id` | …rechaza un segundo pago… | 4. Segundo pago |
| AP-26 | V | Listar pagos | Con recibo y cliente | …lista los pagos… | 4. Listar pagos |
| AP-27 | N | Modificar recibo pagado | 403 | …no permite modificar un recibo pagado | 4. Modificar pagado |
| AP-28 | I | Eliminar recibo pagado | 409 | …no puede eliminar… un recibo pagado | — |
| AP-29 | I | DELETE de pago | 405 en español | …método no permitido responde 405… | 4. Eliminar pago (405) |
| AP-30 | V | Resumen | Indicadores | …el resumen de la API… | 6. Resumen |

## 8. Pruebas unitarias

| ID | Caso | Prueba |
|---|---|---|
| UN-01 | `formato_moneda` (miles, decimales, nulo, negativo) | `tests/Unit/HelpersTest.php` |
| UN-02 | `nombre_mes` (válido e inválido) | `tests/Unit/HelpersTest.php` |

## 9. Pruebas manuales de usabilidad y diseño responsivo

| ID | Caso | Cómo probar | Resultado esperado | Resultado |
|---|---|---|---|---|
| UX-01 | Pestañas en celular | Chrome › F12 › modo dispositivo (iPhone 12, 390 px) | Las pestañas se deslizan de lado; la activa está subrayada; *Cerrar sesión* visible bajo la marca | |
| UX-02 | Tablas en celular | Clientes en 390 px | La tabla se desplaza horizontalmente; la cuenta no se corta | |
| UX-03 | Formularios en celular | Registrar cliente en 390 px | Campos en una columna; botón principal arriba | |
| UX-04 | Ruta de navegación | Clientes › Editar | Muestra Inicio › Clientes › Editar con enlaces | |
| UX-05 | Importe a cobrar | Registrar pago, elegir recibo | Aparece "Importe a cobrar: $…" | |
| UX-06 | Impresión | Comprobante de pago › Imprimir | Vista sin membrete, pestañas ni botones | |
| UX-07 | Cobrar desde el cliente | Clientes › Ver un cliente con adeudo › **Cobrar** | Abre *Registrar pago* con ese recibo elegido y el importe exacto | |
| UX-08 | Teclado | Recorrer el inicio con Tab | Contorno amarillo visible en enlaces y botones; halo azul en campos | |
| UX-07 | Teclado | Tab desde el inicio | Primero aparece "Saltar al contenido"; foco visible | |
