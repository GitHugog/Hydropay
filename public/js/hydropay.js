/* =====================================================================
   HYDROPAY — COMPORTAMIENTO DE LA INTERFAZ (JavaScript sin dependencias)
   1. Pide confirmación antes de enviar formularios de eliminación.
   2. Muestra el importe exacto del recibo elegido al registrar un pago.
   3. Botones "Imprimir".
   (La navegación son pestañas visibles: no necesita JavaScript.)
   ===================================================================== */

// Espera a que el HTML termine de cargar antes de buscar elementos.
document.addEventListener('DOMContentLoaded', function () {
    // ---------- 1. Confirmación de eliminaciones ----------
    // Formularios con data-confirmar (componente <x-boton-eliminar>).
    document.querySelectorAll('form[data-confirmar]').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            // Si el usuario cancela, el formulario no se envía.
            if (!window.confirm(formulario.dataset.confirmar)) {
                evento.preventDefault();
            }
        });
    });

    // ---------- 2. Importe del recibo al registrar un pago ----------
    // Lista de recibos y párrafo donde se muestra el importe.
    var origen = document.querySelector('[data-monto-origen]');
    var destino = document.querySelector('[data-monto-destino]');

    // Solo existe en la pantalla "Registrar pago".
    if (origen && destino) {
        // Texto original del aviso.
        var textoBase = destino.textContent;

        // Actualiza el aviso según la opción elegida.
        var mostrarImporte = function () {
            // Importe guardado en el atributo data-monto de la opción seleccionada.
            var monto = origen.options[origen.selectedIndex].dataset.monto;

            // Si hay recibo elegido se muestra su importe; si no, el texto original.
            destino.textContent = monto ? 'Importe a cobrar: ' + monto + ' (pago completo, sin abonos).' : textoBase;
        };

        // Se ejecuta al cambiar la selección y al cargar la página (recibo preseleccionado).
        origen.addEventListener('change', mostrarImporte);
        mostrarImporte();
    }

    // ---------- 3. Botones de impresión ----------
    document.querySelectorAll('[data-imprimir]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            // Abre el cuadro de impresión del navegador (permite "Guardar como PDF").
            window.print();
        });
    });
});
