# Manual de usuario de HydroPay

HydroPay ayuda a un organismo operador de agua (o comité comunitario) a llevar el padrón de clientes, registrar los recibos de cada periodo y cobrar cada recibo en **un solo pago completo**.

## 1. Roles

| Rol | Qué puede hacer |
|---|---|
| **Administrador** | Todo: además administra usuarios, cancela recibos y elimina registros sin movimientos |
| **Cajero** | Registra y edita clientes y recibos; registra pagos |
| **Consulta** | Solo consulta información y reportes |

Si alguien intenta abrir una sección sin permiso (incluso escribiendo la dirección), aparece la página **403 · Acceso denegado** con el motivo.

## 2. Entrar y salir

1. Abre `http://127.0.0.1:8000`. Si no has iniciado sesión aparece **Iniciar sesión**.
2. Escribe tu correo y contraseña y pulsa **Entrar**. Tras 5 intentos fallidos en un minuto el acceso se bloquea temporalmente.
3. Para salir usa **Cerrar sesión**, arriba a la derecha (en celular, debajo del nombre del sistema).

**¿Olvidaste tu contraseña?** En el login pulsa el enlace, escribe tu correo y abre el enlace que recibes (vence en 60 minutos). Con la sesión iniciada puedes cambiarla en **Cambiar contraseña** (pide la actual; la nueva debe tener 8 caracteres o más, con letras y números).

## 3. Navegación

- **Pestañas:** debajo de la franja azul están Inicio, Clientes, Cobros, Pagos, Reportes y, para el administrador, Zonas y Usuarios. La pestaña subrayada es la sección abierta.
- **Celular:** las pestañas se deslizan con el dedo hacia la izquierda para ver las demás.
- **Mi contraseña** y **Cerrar sesión** están en la franja azul, junto a tu nombre.
- Arriba de cada título aparece la **ruta de navegación** (Inicio › Clientes › Editar) para regresar con un clic.
- Los mensajes verdes confirman una operación; los rojos explican un error. Si un formulario tiene errores, se listan arriba y junto a cada campo, y lo capturado se conserva.

## 4. Panel principal

Empieza con **Buscar cliente**: escribe el nombre, el número de cuenta o la dirección de quien llega a pagar. Debajo están las cifras del servicio (por cobrar, cobrado, cobros pendientes y vencidos); cada una abre su listado ya filtrado. Después vienen los botones **Registrar pago**, **Registrar cliente** y **Registrar cobro**, los últimos pagos y los recibos vencidos.

En la ficha de cada cliente, los recibos sin pagar tienen el botón **Cobrar**, que abre el pago con ese recibo ya elegido.

## 5. Clientes

- **Buscar:** por nombre, cuenta o dirección; filtrar por *Activos* o *Inactivos* y por **zona** (pueblo o localidad), para que cada cajero vea solo a los clientes que atiende.
- **Zona:** al registrar o editar, elige la zona del cliente en *Zona (municipio y localidad)*. Las zonas las da de alta el administrador.
- **Registrar** (*+ Nuevo cliente*): nombre, número de cuenta (único), dirección, teléfono y correo opcionales. Si capturas el correo, el cliente recibirá la confirmación de cada pago. Al guardar se abre el detalle del cliente.
- **Cuota del servicio (solo administrador):** en el recuadro *Cuota del servicio* elige la **periodicidad** (mensual, bimestral o anual) y la **cuota por periodo**. Al guardar, HydroPay crea el recibo del periodo actual y, desde entonces, uno nuevo al iniciar cada periodo: *Noviembre 2026*, *Noviembre-Diciembre 2026* o *Anual 2026*, con fecha límite al final del periodo. El cajero ve la cuota pero no puede cambiarla.
- **Cuánto debe:** la columna **Debe** del listado y el indicador *Adeudo actual* del detalle suman los recibos sin pagar y vencidos.
- **Detalle** (*Ver*): datos, **adeudo actual** y lista de recibos con acceso directo a **Cobrar**.
- **Activo / Inactivo** indica si el registro está habilitado; no indica si debe dinero. Solo los clientes activos reciben recibos nuevos.
- **Eliminar:** solo el administrador y solo si el cliente no tiene recibos.

## 6. Cobros (recibos)

- Cada recibo pertenece a un cliente y a un **periodo** (por ejemplo, *Noviembre 2026*). Un cliente no puede tener dos recibos del mismo periodo.
- Estados: **Sin pagar** (pendiente), **Vencido** (pasó la fecha límite), **Pagado** y **Cancelado**.
- **Registrar:** elige cliente, periodo, monto (máximo dos decimales) y fecha límite. Si la fecha ya pasó, nace vencido.
- **Editar:** solo recibos sin pago. Únicamente el administrador puede **cancelar** o modificar un recibo cancelado.
- Cada noche el sistema marca como vencidos los recibos atrasados y crea los recibos del nuevo periodo para los clientes con cuota (`schedule:work` o tarea programada).
- **Generar recibos del periodo** (botón del administrador en *Cobros*): crea en ese momento los recibos que falten del periodo actual; nunca repite un periodo.
- Los recibos de clientes sin cuota se siguen registrando a mano con *+ Nuevo cobro*.

## 7. Pagos

1. Desde un recibo pulsa **Cobrar** o entra a *Pagos › + Registrar pago*.
2. Elige el recibo: aparece el aviso **Importe a cobrar** con el total exacto.
3. Indica método (efectivo, transferencia, tarjeta o depósito), fecha (no futura), referencia y, si quieres, el comprobante (JPG, PNG o PDF de hasta 2 MB).
4. Pulsa **Confirmar pago completo**. El recibo queda **Pagado** y se muestra el comprobante, que puedes **Imprimir** o guardar como PDF.

Reglas: no se aceptan abonos ni pagos dobles; un recibo cancelado no se cobra; los pagos no se editan ni eliminan. En transferencias y depósitos verifica la acreditación antes de registrar.

## 8. Reportes

*Reportes* muestra el mes actual; usa el selector de mes o los botones **‹ mes anterior** y **mes siguiente ›**. Incluye ingresos del mes por método de pago, recibos registrados en el mes por estado, adeudo vigente, clientes con mayor adeudo vencido y el detalle de pagos. **Descargar CSV** genera un archivo que abre en Excel; **Imprimir** permite guardarlo como PDF.

## 9. Zonas (solo administrador)

*Administración › Zonas* guarda los municipios y localidades (pueblos, colonias o rancherías) del servicio, por ejemplo *Tala › San Isidro Mazatepec*. Registra el **municipio** y la **localidad**; la misma localidad no puede repetirse dentro de un municipio. La columna *Clientes* lleva al listado filtrado por esa zona. Solo se puede eliminar una zona sin clientes asignados.

## 10. Usuarios (solo administrador)

Alta de personal con nombre, correo, rol y contraseña; edición (la contraseña puede dejarse vacía para conservarla) y baja. Protecciones: no puedes cambiar tu propio rol, eliminar tu cuenta ni dejar el sistema sin administradores. Los pagos registrados por un usuario eliminado se conservan.
