<?php
declare(strict_types=1);

if (!is_file(__DIR__ . '/../config.php')) {
    http_response_code(500);
    exit('Falta config.php. Copia config.example.php como config.php y configura la base de datos.');
}

$GLOBALS['config'] = require __DIR__ . '/../config.php';

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/cart.php';
