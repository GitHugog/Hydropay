<?php

// ---------- PRUEBAS UNITARIAS DE LOS HELPERS (app/Support/helpers.php) ----------
// Son funciones puras: no necesitan base de datos ni la aplicación completa.

// formato_moneda agrega signo, separador de miles y dos decimales.
test('formato_moneda da formato de pesos con dos decimales', function () {
    expect(formato_moneda(1250.5))->toBe('$1,250.50');
    expect(formato_moneda('99'))->toBe('$99.00');
    expect(formato_moneda(null))->toBe('$0.00');
    expect(formato_moneda(-15))->toBe('-$15.00');
});

// nombre_mes devuelve el nombre en español o vacío si el número no es válido.
test('nombre_mes devuelve el mes en español', function () {
    expect(nombre_mes(1))->toBe('enero');
    expect(nombre_mes(11))->toBe('noviembre');
    expect(nombre_mes(13))->toBe('');
});
