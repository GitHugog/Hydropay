<?php

// Espacio de nombres de los rasgos (traits) compartidos por los Form Requests.
namespace App\Http\Requests\Concerns;

// Rasgo reutilizable: limpia los textos libres ANTES de validarlos.
// Así se valida exactamente lo que se va a guardar y se evita almacenar HTML/JavaScript (XSS).
trait LimpiaEntradas
{
    /**
     * Quita etiquetas HTML y espacios sobrantes de los campos indicados.
     *
     * @param  array<int, string>  $campos  Nombres de los campos de texto a limpiar.
     */
    protected function limpiarCampos(array $campos): void
    {
        // Aquí se acumulan los valores ya limpios.
        $limpios = [];

        // Se revisa cada campo indicado.
        foreach ($campos as $campo) {
            // Valor enviado por el formulario o por la API.
            $valor = $this->input($campo);

            // Solo se procesan textos (números, archivos o null se dejan igual).
            if (is_string($valor)) {
                // strip_tags elimina etiquetas como <script>; trim quita espacios al inicio y al final.
                $texto = trim(strip_tags($valor));

                // Si después de limpiar no queda nada, se guarda null para que "required" o "nullable" actúen bien.
                $limpios[$campo] = $texto === '' ? null : $texto;
            }
        }

        // merge reemplaza los valores originales de la solicitud por los limpios.
        $this->merge($limpios);
    }
}
