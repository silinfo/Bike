<?php
// Copia este archivo como config.php y ajusta los valores.
return [
    // true solo mientras desarrollas: muestra los errores de PHP en pantalla
    'debug' => false,
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
        // URL pública completa (para Stripe y enlaces en emails). Ej: https://www.velox.bike
        // Si se deja vacía se deduce de la petición actual.
        'app_url'  => '',
    ],
    'shop' => [
        'shipping_cost'      => 9.90,
        'free_shipping_from' => 100.00,
        'tax_rate'           => 0.21, // IVA incluido en los precios
        'currency_code'      => 'eur',
        // Datos que se muestran para pagos por transferencia
        'bank_iban'          => 'ES00 0000 0000 0000 0000 0000',
        'bank_holder'        => 'VELOX Bikes S.L.',
    ],
    // Pago con tarjeta mediante Stripe Checkout. Déjalo vacío para desactivarlo.
    // Claves en https://dashboard.stripe.com/apikeys (usa sk_test_... para pruebas)
    'stripe' => [
        'secret_key'     => '',
        // Secreto del endpoint del webhook (whsec_...) → https://tudominio/stripe-webhook.php
        'webhook_secret' => '',
    ],
    // Envío de emails
    'mail' => [
        // 'smtp' (recomendado), 'mail' (función mail() de PHP) o 'log' (guarda los
        // emails en storage/mails/ sin enviarlos; útil en desarrollo)
        'driver'      => 'log',
        'host'        => 'smtp.tuproveedor.com',
        'port'        => 587,
        'encryption'  => 'tls', // 'tls' (STARTTLS, puerto 587) o 'ssl' (puerto 465)
        'username'    => '',
        'password'    => '',
        'from_email'  => 'pedidos@velox.bike',
        'from_name'   => 'VELOX',
        // Quién recibe los avisos de nuevos pedidos
        'admin_email' => 'hola@velox.bike',
    ],
];
