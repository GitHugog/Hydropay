<?php

// TEXTOS DE HYDROPAY (localización). Se usan con __('hydropay.grupo.clave').
// Centralizar los mensajes aquí evita repetir textos en controladores y vistas
// y permite traducir el sistema agregando lang/<idioma>/hydropay.php.
return [

    // Etiquetas visibles de los estados de un recibo.
    'estados' => [
        'pendiente' => 'Sin pagar',
        'pagado' => 'Pagado',
        'vencido' => 'Vencido',
        'cancelado' => 'Cancelado',
    ],

    // Nombres visibles de los métodos de pago.
    'metodos' => [
        'efectivo' => 'Efectivo',
        'transferencia' => 'Transferencia',
        'tarjeta' => 'Tarjeta',
        'deposito' => 'Depósito',
    ],

    // Nombres visibles de los roles.
    'roles' => [
        'admin' => 'Administrador',
        'cajero' => 'Cajero',
        'consulta' => 'Consulta',
    ],

    // Mensajes de permisos (Policies y middleware VerificarRol).
    'permisos' => [
        'solo_consulta' => 'Tu rol solo permite consultar información.',
        'solo_admin_elimina' => 'Solo un administrador puede eliminar registros.',
        'rol_insuficiente' => 'Tu rol no tiene permiso para entrar a esta sección.',
    ],

    // Sesión y contraseñas.
    'auth' => [
        'sesion_cerrada' => 'Sesión cerrada correctamente.',
        'enlace_enviado' => 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.',
        'password_restablecida' => 'Contraseña actualizada. Ya puedes iniciar sesión.',
        'password_cambiada' => 'Contraseña actualizada correctamente.',
    ],

    // Módulo de clientes.
    'clientes' => [
        'creado' => 'Cliente registrado correctamente.',
        'actualizado' => 'Cliente actualizado correctamente.',
        'eliminado' => 'Cliente eliminado correctamente.',
        'no_eliminar_con_cobros' => 'No se puede eliminar este cliente porque tiene recibos asociados.',
        'recibo_generado' => 'Se generó el recibo de :periodo por :monto.',
    ],

    // Mensajes del catálogo de zonas.
    'zonas' => [
        'creada' => 'Zona registrada correctamente.',
        'actualizada' => 'Zona actualizada correctamente.',
        'eliminada' => 'Zona eliminada correctamente.',
        'con_clientes' => 'No se puede eliminar esta zona porque tiene clientes asignados. Cámbialos de zona primero.',
    ],

    // Periodicidades de la cuota del servicio.
    'periodicidades' => [
        'mensual' => 'Mensual',
        'bimestral' => 'Bimestral',
        'anual' => 'Anual',
    ],

    // Módulo de recibos.
    'cobros' => [
        'creado' => 'Recibo registrado correctamente.',
        'actualizado' => 'Recibo actualizado correctamente.',
        'eliminado' => 'Recibo eliminado correctamente.',
        'no_eliminar_con_pago' => 'No se puede eliminar este recibo porque ya tiene un pago registrado.',
        'error_eliminar' => 'No se pudo eliminar el recibo. Intenta de nuevo.',
        'liquidado_no_editable' => 'Este recibo ya fue liquidado y no puede modificarse.',
        'cancelado_solo_admin' => 'Solo un administrador puede modificar un recibo cancelado.',
        'cancelar_solo_admin' => 'Solo un administrador puede cancelar un recibo.',
        'generados' => '{0} No había recibos por generar: todos los clientes con cuota ya tienen su recibo del periodo.|{1} Se generó 1 recibo del periodo actual.|[2,*] Se generaron :total recibos del periodo actual.',
    ],

    // Módulo de pagos.
    'pagos' => [
        'creado' => 'Pago completo registrado correctamente. El recibo quedó liquidado.',
        'error' => 'No se pudo registrar el pago. Intenta de nuevo.',
        'no_admite_pago' => 'Este recibo ya está pagado o cancelado y no admite otro pago.',
        'sin_abonos' => 'No se aceptan abonos. Debe registrarse el importe completo del recibo: :monto.',
        'duplicado' => 'El recibo ya fue pagado en otra operación. Actualiza la página y verifica.',
        'inmutable' => 'Los pagos registrados no se modifican ni se eliminan para conservar el historial financiero.',
    ],

    // Módulo de usuarios.
    'usuarios' => [
        'creado' => 'Usuario creado correctamente.',
        'actualizado' => 'Usuario actualizado correctamente.',
        'eliminado' => 'Usuario eliminado correctamente.',
        'no_eliminarse' => 'No puedes eliminar tu propia cuenta.',
        'ultimo_admin_rol' => 'No puedes retirar el rol al último administrador del sistema.',
        'ultimo_admin_eliminar' => 'No puedes eliminar al último administrador del sistema.',
    ],

    // Mensajes de la API REST.
    'api' => [
        'credenciales_invalidas' => 'Las credenciales no coinciden con nuestros registros.',
        'token_emitido' => 'Token emitido correctamente. Envíalo en el encabezado Authorization: Bearer {token}.',
        'token_revocado' => 'Token revocado. La sesión de la API se cerró correctamente.',
        'no_autenticado' => 'No autenticado. Envía un token válido en el encabezado Authorization: Bearer {token}.',
        'sin_habilidad' => 'Tu token no tiene la habilidad necesaria para esta operación.',

        // Mensajes genéricos por código HTTP.
        'errores' => [
            '400' => 'Solicitud incorrecta.',
            '403' => 'No tienes permiso para realizar esta acción.',
            '404' => 'El recurso solicitado no existe.',
            '405' => 'Método HTTP no permitido para esta ruta.',
            '409' => 'La operación entra en conflicto con el estado actual del registro.',
            '419' => 'La sesión expiró. Vuelve a intentarlo.',
            '429' => 'Demasiadas solicitudes. Espera un momento antes de volver a intentar.',
            '500' => 'Ocurrió un error interno. Intenta más tarde.',
            '503' => 'El servicio está en mantenimiento. Intenta más tarde.',
            'general' => 'No se pudo completar la solicitud.',
        ],
    ],
];
