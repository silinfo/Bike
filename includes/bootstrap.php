<?php
declare(strict_types=1);

// Con PHP antiguo mostramos instrucciones en lugar de un error de sintaxis
if (PHP_VERSION_ID < 80100) {
    require __DIR__ . '/php-version-error.php';
    exit;
}

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
    // Si la carpeta de sesiones del servidor no existe o no se puede escribir
    // (p. ej. en cPanel tras cambiar de versión de PHP queda apuntando a la
    // antigua), usamos una propia dentro de storage/, protegida por .htaccess.
    $savePath = (string)session_save_path();
    $saveDir = substr($savePath, (int)strrpos(';' . $savePath, ';')); // admite "N;/ruta"
    if ($saveDir === '' || !@is_dir($saveDir) || !@is_writable($saveDir)) {
        $ownDir = __DIR__ . '/../storage/sessions';
        if (!is_dir($ownDir)) @mkdir($ownDir, 0700, true);
        if (is_dir($ownDir) && is_writable($ownDir)) {
            session_save_path($ownDir);
            // En carpeta propia PHP debe limpiar él mismo las sesiones caducadas
            ini_set('session.gc_probability', '1');
            ini_set('session.gc_divisor', '100');
        }
    }

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
