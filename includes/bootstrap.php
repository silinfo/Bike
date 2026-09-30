<?php
declare(strict_types=1);

if (!is_file(__DIR__ . '/../config.php')) {
    // Primera visita en el hosting: al instalador
    if (is_file(__DIR__ . '/../install.php') && PHP_SAPI !== 'cli') {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header('Location: ' . preg_replace('#/admin$#', '', $base) . '/install.php');
        exit;
    }
    http_response_code(500);
    exit('Falta config.php. Copia config.example.php como config.php y configura la base de datos.');
}

if (!is_file(__DIR__ . '/../vendor/autoload.php')) {
    http_response_code(500);
    exit('Faltan las dependencias. Ejecuta "composer install" en la carpeta del proyecto.');
}
require __DIR__ . '/../vendor/autoload.php';

$GLOBALS['config'] = require __DIR__ . '/../config.php';

// En producción no se muestran errores al visitante (quedan en el log del servidor)
ini_set('display_errors', !empty($GLOBALS['config']['debug']) ? '1' : '0');
error_reporting(E_ALL);

// El webhook de Stripe y los scripts CLI no usan sesión ($noSession = true antes del require)
if (PHP_SAPI !== 'cli' && empty($noSession)) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/cart.php';
require __DIR__ . '/mailer.php';
require __DIR__ . '/orders.php';
require __DIR__ . '/stripe.php';
