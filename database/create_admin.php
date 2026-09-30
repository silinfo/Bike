<?php
// Crea o actualiza un usuario administrador.
// Uso: php database/create_admin.php email@dominio.com "contraseña" "Nombre"
if (PHP_SAPI !== 'cli') exit('Solo desde la línea de comandos.');
$GLOBALS['config'] = require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/db.php';

[$_, $email, $pass, $name] = $argv + [null, null, null, 'Administrador'];
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen((string)$pass) < 8) {
    fwrite(STDERR, "Uso: php database/create_admin.php email \"contraseña (mín. 8)\" [nombre]\n");
    exit(1);
}
db_exec('INSERT INTO admin_users (email, name, password_hash) VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash)',
    [$email, $name, password_hash($pass, PASSWORD_DEFAULT)]);
echo "Administrador {$email} listo.\n";
