<?php
/*
 * Instalador web. BÓRRALO del servidor en cuanto termines la instalación.
 *
 * Este archivo usa a propósito sintaxis compatible con PHP antiguo (5.x/7.x)
 * para poder avisar si el hosting tiene una versión de PHP demasiado vieja.
 */
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    require dirname(__FILE__) . '/includes/php-version-error.php';
    exit;
}
require __DIR__ . '/includes/installer.php';
