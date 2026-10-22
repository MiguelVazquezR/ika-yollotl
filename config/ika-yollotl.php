<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Información del centro
    |--------------------------------------------------------------------------
    |
    | Datos públicos que se comparten con Vue a través de Inertia.
    |
    */

    'name' => 'Ika Yollotl',
    'tagline' => 'Centro de rehabilitación física',
    'philosophy' => 'Sanar desde el corazón, acompañarte en cada paso.',

    'phone' => '+52 33 1061 5276',
    'whatsapp' => '523310615276',
    'email' => 'ika_yollotl@hotmail.com',

    'address' => [
        'street' => 'C. Magnolias 964, La Mora, Ex Hacienda',
        'city' => 'Zapopan',
        'state' => 'Jal.',
        'postal_code' => '45138',
        'full' => 'C. Magnolias 964, La Mora, Ex Hacienda, 45138 Zapopan, Jal., México',
        'short' => 'C. Magnolias 964, La Mora, Zapopan',
    ],

    /*
    | Coordenadas exactas del centro. Se usan para el mapa embebido y
    | para "Cómo llegar" porque la geocodificación de la dirección
    | puede desplazar el pin a otra cuadra.
    */
    'maps' => [
        'query' => '20.731445962945354,-103.42311415364122',
        'coordinates' => [
            'lat' => 20.731445962945354,
            'lng' => -103.42311415364122,
        ],
        'embed_url' => 'https://www.google.com/maps?q=20.731445962945354,-103.42311415364122&z=17&hl=es&output=embed',
        'directions_url' => 'https://www.google.com/maps/dir/?api=1&destination=20.731445962945354,-103.42311415364122',
    ],

    'hours' => [
        'days' => 'Lunes a viernes',
        'open' => '09:00',
        'close' => '19:00',
        'note' => 'Únicamente previa cita',
        'closed' => 'Sábados y domingos cerrado',
    ],

];
