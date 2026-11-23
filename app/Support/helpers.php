<?php

// HELPERS DE HYDROPAY
// Funciones globales reutilizadas en vistas, reportes y API.
// Composer las carga automáticamente porque están declaradas en composer.json → "autoload" → "files".

// Solo se declara la función si no existe otra con el mismo nombre (evita choques con paquetes).
if (! function_exists('formato_moneda')) {
    /**
     * Convierte un importe a texto de moneda: 1250.5 → "$1,250.50".
     */
    function formato_moneda(int|float|string|null $monto): string
    {
        // Si el valor no es numérico (por ejemplo, null) se toma como cero para no romper la vista.
        $numero = is_numeric($monto) ? (float) $monto : 0.0;

        // El signo negativo se coloca antes del símbolo de pesos: -$15.00.
        $signo = $numero < 0 ? '-' : '';

        // number_format agrega separador de miles (coma) y fija dos decimales (punto).
        return $signo.'$'.number_format(abs($numero), 2);
    }
}

// Nombre del mes en español a partir de su número.
if (! function_exists('nombre_mes')) {
    /**
     * Devuelve el nombre del mes en español: 1 → "enero", 12 → "diciembre".
     */
    function nombre_mes(int $mes): string
    {
        // Arreglo fijo para no depender del idioma instalado en el sistema operativo.
        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        // Si el número no corresponde a un mes válido se devuelve una cadena vacía.
        return $meses[$mes] ?? '';
    }
}

// Texto visible del estado de un recibo (el valor interno se guarda en minúsculas).
if (! function_exists('etiqueta_estado')) {
    /**
     * Traduce el estado interno de un recibo al texto que ve el usuario: "pendiente" → "Sin pagar".
     */
    function etiqueta_estado(?string $estado): string
    {
        // Clave del archivo de traducciones lang/es/hydropay.php.
        $clave = 'hydropay.estados.'.$estado;

        // Si existe la traducción se usa; si no, se muestra el valor con mayúscula inicial.
        return trans()->has($clave) ? __($clave) : ucfirst((string) $estado);
    }
}
