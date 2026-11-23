# Documentación de la API REST de HydroPay (v1)

API propia de HydroPay para que otras aplicaciones (una app móvil de cobro en campo, Postman, un sistema contable) consulten y registren información con las **mismas reglas** que la interfaz web.

| Dato | Valor |
|---|---|
| URL base (local) | `http://127.0.0.1:8000/api/v1` |
| Formato | JSON (enviar `Accept: application/json`) |
| Autenticación | Token Bearer de **Laravel Sanctum** |
| Vigencia del token | 8 horas (`HYDROPAY_API_TOKEN_HORAS`) |
| Límite de peticiones | Login: 5 por minuto por IP · Resto: 60 por minuto |
| Idioma de mensajes | Español |
| Archivo de rutas | `routes/api.php` |
| Colección de pruebas | `docs/postman/HydroPay_API.postman_collection.json` |

## 1. Autenticación y habilidades del token

1. Se envía `POST /auth/login` con correo y contraseña.
2. La respuesta incluye `token`. Todas las demás solicitudes llevan el encabezado:
   `Authorization: Bearer {token}`
3. Cada token recibe **habilidades** según el rol del usuario. El middleware `abilities` de Sanctum las revisa en cada ruta, y además las **Policies** revisan el rol real del usuario (defensa en profundidad).

| Rol | Habilidades del token | Puede |
|---|---|---|
| `admin` | `leer`, `escribir`, `eliminar` | Todo, incluidas eliminaciones |
| `cajero` | `leer`, `escribir` | Consultar, registrar y editar; registrar pagos |
| `consulta` | `leer` | Solo consultar |

Al cambiar el rol o la contraseña de un usuario, sus tokens se revocan automáticamente. Los tokens vencidos se eliminan cada día con `sanctum:prune-expired`.

## 2. Resumen de endpoints

| Método | Endpoint | Habilidad | Descripción |
|---|---|---|---|
| POST | `/auth/login` | — (público) | Inicia sesión y entrega un token |
| POST | `/auth/logout` | token válido | Revoca el token usado |
| GET | `/auth/yo` | token válido | Perfil del usuario y habilidades del token |
| GET | `/resumen` | `leer` | Indicadores del panel principal |
| GET | `/clientes` | `leer` | Lista paginada de clientes |
| GET | `/clientes/{id}` | `leer` | Detalle de un cliente |
| GET | `/clientes/{id}/estado-cuenta` | `leer` | Recibos por pagar, total adeudado y último pago |
| GET | `/zonas` | `leer` | Catálogo de zonas (municipio, localidad y número de clientes) |
| POST | `/clientes` | `escribir` | Registra un cliente |
| PUT/PATCH | `/clientes/{id}` | `escribir` | Actualiza un cliente |
| DELETE | `/clientes/{id}` | `eliminar` | Elimina un cliente sin recibos |
| GET | `/cobros` | `leer` | Lista paginada de recibos |
| GET | `/cobros/{id}` | `leer` | Detalle de un recibo con su pago |
| POST | `/cobros` | `escribir` | Registra un recibo |
| PUT/PATCH | `/cobros/{id}` | `escribir` | Actualiza un recibo no pagado |
| DELETE | `/cobros/{id}` | `eliminar` | Elimina un recibo sin pago |
| GET | `/pagos` | `leer` | Historial paginado de pagos |
| GET | `/pagos/{id}` | `leer` | Detalle de un pago |
| POST | `/pagos` | `escribir` | Registra el pago completo de un recibo |

No existen rutas para editar o eliminar pagos: son registros financieros inmutables (una solicitud `DELETE /pagos/{id}` responde **405**).

## 3. Códigos de respuesta

| Código | Significado en HydroPay | Ejemplo de `message` |
|---|---|---|
| 200 | Consulta o actualización correcta | — |
| 201 | Registro creado (token, cliente, recibo o pago) | — |
| 401 | Sin token, token revocado/vencido o credenciales incorrectas | `No autenticado. Envía un token válido en el encabezado Authorization: Bearer {token}.` |
| 403 | El token no tiene la habilidad o el rol no tiene permiso | `Tu token no tiene la habilidad necesaria para esta operación.` / `Tu rol solo permite consultar información.` / `Este recibo ya fue liquidado y no puede modificarse.` |
| 404 | El registro o la ruta no existen | `El recurso solicitado no existe.` |
| 405 | Método HTTP no permitido | `Método HTTP no permitido para esta ruta.` |
| 409 | Conflicto de integridad (cliente con recibos, recibo con pago) | `No se puede eliminar este cliente porque tiene recibos asociados.` |
| 422 | Datos inválidos o regla de negocio | `message` + objeto `errors` con un arreglo por campo |
| 429 | Demasiadas solicitudes | `Demasiadas solicitudes. Espera un momento antes de volver a intentar.` |
| 500 | Error inesperado (queda en el log) | `No se pudo registrar el pago. Intenta de nuevo.` |

Formato de un error 422:

```json
{
  "message": "El campo nombre es obligatorio. (y 2 errores más)",
  "errors": {
    "nombre": ["El campo nombre es obligatorio."],
    "numero_cuenta": ["El campo número de cuenta es obligatorio."],
    "direccion": ["El campo dirección es obligatorio."]
  }
}
```

## 4. Detalle de endpoints

### 4.1 POST `/auth/login`

| Campo | Tipo | Reglas |
|---|---|---|
| `email` | texto | obligatorio, correo válido |
| `password` | texto | obligatorio |
| `dispositivo` | texto | opcional, máx. 60 (nombre del cliente: "Postman", "App móvil") |

Respuesta **201**:

```json
{
  "message": "Token emitido correctamente. Envíalo en el encabezado Authorization: Bearer {token}.",
  "token": "3|Xh2q...",
  "tipo": "Bearer",
  "expira_en": "2026-11-22T06:00:00-06:00",
  "habilidades": ["leer", "escribir"],
  "usuario": { "id": 2, "nombre": "Cajero HydroPay", "email": "cajero@hydropay.test", "rol": "cajero" }
}
```

Errores: **401** credenciales incorrectas · **422** campos vacíos · **429** más de 5 intentos por minuto.

### 4.2 POST `/auth/logout` y GET `/auth/yo`

`logout` elimina de la base el token usado (respuesta 200). `yo` devuelve `usuario`, `habilidades` y `expira_en`.

### 4.3 GET `/clientes`

Parámetros opcionales: `busqueda` (nombre, cuenta o dirección), `activo` (`1` o `0`), `zona_id`, `por_pagina` (1 a 50, por defecto 15), `page`.

```json
{
  "data": [
    {
      "id": 7, "numero_cuenta": "HP-104233", "nombre": "María López",
      "direccion": "Calle Hidalgo 10, Tala", "telefono": "3312345678",
      "email": null, "activo": true, "periodicidad": "mensual", "tarifa": 150.0,
      "zona_id": 2, "zona": { "id": 2, "municipio": "Tala", "localidad": "Cuisillos" }, "recibos": 3,
      "creado_en": "2026-11-09T10:15:00-06:00", "actualizado_en": "2026-11-09T10:15:00-06:00"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "per_page": 15, "total": 25, "last_page": 2 }
}
```

### 4.4 POST `/clientes` y PUT `/clientes/{id}`

| Campo | Reglas |
|---|---|
| `nombre` | obligatorio, texto, máx. 120 |
| `numero_cuenta` | obligatorio, máx. 30, único |
| `direccion` | obligatorio, máx. 255 |
| `telefono` | opcional, 7 a 20 caracteres: dígitos, espacios, `+`, `-`, `( )` |
| `email` | opcional, correo válido |
| `activo` | opcional, booleano (al crear, si se omite queda `true`) |
| `zona_id` | opcional, id de una zona existente (`GET /zonas`) |
| `periodicidad` | solo administrador: `mensual`, `bimestral` o `anual` (por defecto `mensual`) |
| `tarifa` | solo administrador: cuota por periodo, 0 a 999,999.99, máx. 2 decimales; con cuota se crea el recibo del periodo actual |

Los textos se limpian de etiquetas HTML antes de validar. PUT y PATCH requieren el cuerpo completo. Respuestas: **201/200** con el cliente · **422** validación · **403** sin permiso.

### 4.5 GET `/clientes/{id}/estado-cuenta`

```json
{
  "data": {
    "cliente": { "id": 7, "numero_cuenta": "HP-104233", "nombre": "María López", "...": "..." },
    "recibos_por_pagar": [
      { "id": 21, "periodo": "Octubre 2026", "monto": 245.5, "estado": "vencido", "estado_texto": "Vencido", "saldo": 245.5, "pago": null }
    ],
    "total_adeudo": 245.5,
    "recibos_vencidos": 1,
    "ultimo_pago": { "id": 12, "monto": 230, "metodo": "efectivo", "fecha_pago": "2026-09-08", "...": "..." }
  }
}
```

### 4.6 DELETE `/clientes/{id}`

Solo token con `eliminar` (administrador). **200** eliminado · **409** el cliente tiene recibos · **403** sin habilidad.

### 4.7 GET `/cobros`

Parámetros: `estado` (`pendiente`, `vencido`, `pagado`, `cancelado`), `busqueda` (periodo, cliente o cuenta), `cliente_id`, `por_pagina`, `page`. Cada recibo incluye `cliente`, `pago` (o `null`), `estado_texto` y `saldo`.

### 4.8 POST `/cobros` y PUT `/cobros/{id}`

| Campo | Reglas |
|---|---|
| `cliente_id` | obligatorio; debe existir (al crear, además, estar activo) |
| `periodo` | obligatorio, máx. 30, único para el mismo cliente |
| `monto` | obligatorio, numérico, mayor a 0, máximo dos decimales |
| `fecha_vencimiento` | opcional, fecha `AAAA-MM-DD` |
| `descripcion` | opcional, máx. 500 |
| `estado` | **solo en PUT**: `pendiente` o `cancelado` (cancelar requiere administrador) |

Reglas de negocio: un recibo pagado no se modifica (**403**); un recibo cancelado solo lo modifica un administrador (**403**); si la fecha límite ya pasó, el recibo queda `vencido`.

### 4.9 POST `/pagos`

Registra el **pago completo** de un recibo pendiente o vencido. Acepta JSON o `multipart/form-data` (para adjuntar `comprobante`).

| Campo | Reglas |
|---|---|
| `cobro_id` | obligatorio, recibo existente |
| `metodo` | obligatorio: `efectivo`, `transferencia`, `tarjeta`, `deposito` |
| `referencia` | opcional, máx. 100 |
| `fecha_pago` | obligatoria, no posterior a hoy |
| `comprobante` | opcional, JPG/PNG/PDF, máx. 2 MB (se guarda en almacenamiento privado) |
| `monto` | opcional; si se envía debe ser **exactamente** el importe del recibo |

El importe se toma del recibo en el servidor. Respuestas: **201** con el pago y el recibo en estado `pagado` · **422** `cobro_id` (recibo pagado, cancelado o pagado al mismo tiempo por otro cajero) · **422** `monto` (abono o importe distinto).

### 4.10 GET `/resumen`

```json
{
  "data": {
    "clientes": 25, "clientes_activos": 23, "pagos": 28,
    "pendientes": 20, "vencidos": 27,
    "por_estado": { "pagado": { "cantidad": 28, "monto": 11240.35 }, "...": {} },
    "cobrado": 11240.35, "por_cobrar": 19730.12,
    "generado_en": "2026-11-21T22:10:00-06:00"
  }
}
```

## 5. Pruebas de la API

- **Automatizadas (Pest):** `tests/Feature/Api/ApiAutenticacionTest.php`, `ApiClientesTest.php` y `ApiCobrosPagosTest.php` (`php artisan test --filter=Api`).
- **Manuales (Postman):** importa `docs/postman/HydroPay_API.postman_collection.json`. Ejecuta primero la carpeta *1. Autenticación* (guarda los tokens de los tres roles) y después las demás con el **Collection Runner**. Cada solicitud verifica automáticamente el código HTTP esperado: datos válidos, datos inválidos, campos vacíos, usuario autorizado y no autorizado.

## 6. Ejemplos con cURL (PowerShell: usa `curl.exe`)

```bash
# Obtener token
curl.exe -s -X POST http://127.0.0.1:8000/api/v1/auth/login -H "Accept: application/json" -H "Content-Type: application/json" -d "{\"email\":\"cajero@hydropay.test\",\"password\":\"HydroPayCajero2026!\"}"

# Consultar clientes con el token
curl.exe -s http://127.0.0.1:8000/api/v1/clientes -H "Accept: application/json" -H "Authorization: Bearer PEGA_AQUI_EL_TOKEN"
```
