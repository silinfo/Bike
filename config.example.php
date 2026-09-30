<?php
// Copia este archivo como config.php y ajusta los valores.
return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'bike_bike',
        'user'    => 'silinfo_bike',
        'pass'    => 'silinfo_Bike@07',
        'charset' => 'utf8mb4',
    ],
    'site' => [
        'name'     => 'VELOX',
        'tagline'  => 'Bicicletas hechas para rodar',
        'email'    => 'hola@velox.bike',
        'phone'    => '+34 600 000 000',
        'address'  => 'Calle del Pedal 12, 28001 Madrid',
        'currency' => '€',
        // URL base sin barra final ('' si está en la raíz del dominio)
        'base_url' => '',
    ],
    'shop' => [
        'shipping_cost'      => 9.90,
        'free_shipping_from' => 100.00,
        'tax_rate'           => 0.21, // IVA incluido en los precios
    ],
];
