<?php

// Configuración propia de HydroPay. Los valores se pueden ajustar desde el archivo .env
// sin tocar el código. Se leen en el sistema con config('hydropay.clave').
return [

    // Nombre del organismo operador que aparece en el encabezado de los reportes.
    'organismo' => env('HYDROPAY_ORGANISMO', 'Organismo operador de agua'),

    // Horas de vigencia de cada token de la API; al vencer, el cliente debe iniciar sesión otra vez.
    'api_token_horas' => (int) env('HYDROPAY_API_TOKEN_HORAS', 8),

    // Registros por página que entrega la API si el cliente no indica otro valor.
    'api_por_pagina' => 15,

    // Máximo de registros por página que acepta la API (evita respuestas demasiado grandes).
    'api_por_pagina_max' => 50,

    // Registros por página en los listados de la interfaz web.
    'web_por_pagina' => 10,
];
