<?php
/*
 * Instalador web (para hostings sin SSH como HostGator, SiteGround…).
 * Crea config.php, las tablas y el usuario administrador.
 * BÓRRALO del servidor en cuanto termines la instalación.
 */
declare(strict_types=1);
ini_set('display_errors', '1');

$root = __DIR__;
$lock = $root . '/storage/installed.lock';

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Sustituye el valor de una clave dentro de una sección de config.example.php
// conservando los comentarios del archivo.
function config_set(string $php, string $section, string $key, string|int|bool $value): string
{
    $export = var_export($value, true);
    return preg_replace_callback(
        "/('" . preg_quote($section, '/') . "'\s*=>\s*\[)(.*?)(\n    \],)/s",
        fn($m) => $m[1] . preg_replace("/('" . preg_quote($key, '/') . "'\s*=>\s*)(?:'(?:[^'\\\\]|\\\\.)*'|[\w.\-]+)/", '${1}' . addcslashes($export, '\\$'), $m[2], 1) . $m[3],
        $php, 1
    );
}

// Ejecuta un archivo .sql sentencia a sentencia
function run_sql_file(PDO $pdo, string $file): void
{
    $sql = preg_replace('/^\s*--.*$/m', '', file_get_contents($file));
    foreach (preg_split('/;\s*\n/', $sql) as $stmt) {
        if (trim($stmt) !== '') $pdo->exec($stmt);
    }
}

// --- Requisitos ---
$checks = [
    'PHP 8.1 o superior (tienes ' . PHP_VERSION . ')' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'Extensión pdo_mysql'  => extension_loaded('pdo_mysql'),
    'Extensión curl (Stripe)' => extension_loaded('curl'),
    'Extensión mbstring'   => extension_loaded('mbstring'),
    'Extensión fileinfo (subida de imágenes)' => extension_loaded('fileinfo'),
    'Carpeta vendor/ (dependencias)' => is_file($root . '/vendor/autoload.php'),
    'Permiso de escritura en la carpeta de la tienda (config.php)' => is_writable($root) || is_writable($root . '/config.php'),
    'Permiso de escritura en storage/' => is_writable($root . '/storage'),
    'Permiso de escritura en uploads/' => is_writable($root . '/uploads'),
];
$requirementsOk = !in_array(false, $checks, true);

// URL detectada
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
$baseUrl = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$appUrl = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $baseUrl;

$f = [
    'db_host' => 'localhost', 'db_name' => '', 'db_user' => '', 'db_pass' => '',
    'site_name' => 'VELOX', 'site_email' => '', 'app_url' => $appUrl,
    'admin_name' => '', 'admin_email' => '', 'admin_pass' => '', 'demo' => '1',
];
$errors = [];
$done = false;

if (!is_file($lock) && $_SERVER['REQUEST_METHOD'] === 'POST' && $requirementsOk) {
    foreach ($f as $k => $_) $f[$k] = trim((string)($_POST[$k] ?? ''));
    $f['db_pass'] = (string)($_POST['db_pass'] ?? '');
    $f['admin_pass'] = (string)($_POST['admin_pass'] ?? '');

    // Solo base de datos local: evita que un tercero instale contra un servidor suyo
    if (!in_array(strtolower($f['db_host']), ['localhost', '127.0.0.1', '::1'], true)) $errors[] = 'El servidor de base de datos debe ser «localhost».';
    if ($f['db_name'] === '' || $f['db_user'] === '') $errors[] = 'Indica el nombre de la base de datos y el usuario.';
    if (!filter_var($f['admin_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'El email del administrador no es válido.';
    if (strlen($f['admin_pass']) < 8) $errors[] = 'La contraseña del administrador debe tener al menos 8 caracteres.';
    if ($f['site_email'] !== '' && !filter_var($f['site_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'El email de la tienda no es válido.';
    if (!filter_var($f['app_url'], FILTER_VALIDATE_URL)) $errors[] = 'La URL de la tienda no es válida.';

    if (!$errors) {
        try {
            $pdo = new PDO("mysql:host={$f['db_host']};dbname={$f['db_name']};charset=utf8mb4", $f['db_user'], $f['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (PDOException $ex) {
            $errors[] = 'No se puede conectar a la base de datos: ' . $ex->getMessage();
        }
    }
    if (!$errors && $pdo->query("SHOW TABLES LIKE 'products'")->fetchColumn()) {
        $errors[] = 'Esa base de datos ya contiene una tienda instalada. Usa una base de datos vacía (así no se borra nada).';
    }

    if (!$errors) {
        try {
            run_sql_file($pdo, $root . '/database/schema.sql');
            if ($f['demo'] === '1') run_sql_file($pdo, $root . '/database/seed.sql');
            $pdo->prepare('INSERT INTO admin_users (email, name, password_hash) VALUES (?,?,?)')
                ->execute([$f['admin_email'], $f['admin_name'] ?: 'Administrador', password_hash($f['admin_pass'], PASSWORD_DEFAULT)]);

            $cfg = file_get_contents($root . '/config.example.php');
            $cfg = config_set($cfg, 'db', 'host', $f['db_host']);
            $cfg = config_set($cfg, 'db', 'name', $f['db_name']);
            $cfg = config_set($cfg, 'db', 'user', $f['db_user']);
            $cfg = config_set($cfg, 'db', 'pass', $f['db_pass']);
            $cfg = config_set($cfg, 'site', 'name', $f['site_name'] ?: 'VELOX');
            $cfg = config_set($cfg, 'site', 'base_url', $baseUrl);
            $cfg = config_set($cfg, 'site', 'app_url', rtrim($f['app_url'], '/'));
            if ($f['site_email'] !== '') {
                $cfg = config_set($cfg, 'site', 'email', $f['site_email']);
                $cfg = config_set($cfg, 'mail', 'admin_email', $f['site_email']);
                $cfg = config_set($cfg, 'mail', 'from_email', $f['site_email']);
            }
            $cfg = config_set($cfg, 'mail', 'from_name', $f['site_name'] ?: 'VELOX');

            if (file_put_contents($root . '/config.php', $cfg) === false) throw new RuntimeException('No se pudo escribir config.php.');
            @chmod($root . '/config.php', 0640);
            file_put_contents($lock, date('c'));
            $done = true;
        } catch (Throwable $ex) {
            $errors[] = 'Error durante la instalación: ' . $ex->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Instalación de la tienda</title>
<style>
    :root { --accent: #d7263d; --line: #e4e2dd; --muted: #6d6d6d; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; background: #0e0e0e; color: #1c1c1c; line-height: 1.5; padding: 32px 16px; }
    .box { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 32px; }
    h1 { margin: 0 0 4px; font-size: 1.8rem; text-transform: uppercase; letter-spacing: .04em; }
    h2 { font-size: 1.05rem; text-transform: uppercase; letter-spacing: .06em; margin: 28px 0 12px; border-bottom: 1px solid var(--line); padding-bottom: 6px; }
    p.muted, small { color: var(--muted); }
    label { display: block; font-weight: 600; font-size: .9rem; margin-bottom: 12px; }
    input[type=text], input[type=password], input[type=email], input[type=url] { display: block; width: 100%; margin-top: 4px; padding: 10px 12px; border: 1px solid var(--line); border-radius: 6px; font: inherit; }
    .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .check { display: flex; gap: 8px; align-items: center; font-weight: 500; }
    button, .btn { display: inline-block; background: var(--accent); color: #fff; border: 0; padding: 12px 24px; border-radius: 6px; font: inherit; font-weight: 700; cursor: pointer; text-decoration: none; }
    ul.req { list-style: none; padding: 0; margin: 0; }
    ul.req li { padding: 4px 0; }
    .ok { color: #2d8a4e; } .ko { color: var(--accent); font-weight: 600; }
    .error { background: #fdeaea; color: #8f1d1d; padding: 12px 16px; border-radius: 6px; margin-bottom: 12px; }
    .success { background: #e6f4ea; color: #1e5e34; padding: 12px 16px; border-radius: 6px; }
    .warn { background: #fff4db; color: #8a5a00; padding: 12px 16px; border-radius: 6px; margin: 16px 0; }
    code { background: #f5f4f0; padding: 2px 6px; border-radius: 4px; font-size: .88em; word-break: break-all; }
    @media (max-width: 520px) { .row { grid-template-columns: 1fr; } .box { padding: 22px; } }
</style>
</head>
<body>
<div class="box">
<?php if ($done): ?>
    <h1>¡Tienda instalada!</h1>
    <p class="success">Base de datos creada, usuario administrador listo y <code>config.php</code> generado.</p>
    <div class="warn"><strong>Importante:</strong> borra ahora el archivo <code>install.php</code> del servidor (Administrador de archivos de cPanel).</div>
    <p><a class="btn" href="<?= h($baseUrl) ?>/">Ver la tienda</a> &nbsp; <a class="btn" href="<?= h($baseUrl) ?>/admin/">Entrar al panel</a></p>
    <h2>Siguientes pasos</h2>
    <ol>
        <li>Revisa en <code>config.php</code> la dirección, el teléfono, el IBAN y los datos SMTP para los emails (ahora en modo <code>log</code>: no se envían).</li>
        <li>Para cobrar con tarjeta, añade tus claves de Stripe (ver README).</li>
        <li>Crea el cron en cPanel → <em>Cron Jobs</em> (cada 15 minutos):<br><code>php <?= h($root) ?>/bin/cron.php</code></li>
    </ol>
<?php elseif (is_file($lock)): ?>
    <h1>Ya instalada</h1>
    <p>La tienda ya está instalada. Por seguridad, <strong>borra el archivo <code>install.php</code></strong> del servidor.</p>
    <p><a class="btn" href="<?= h($baseUrl) ?>/">Ir a la tienda</a></p>
<?php else: ?>
    <h1>Instalación</h1>
    <p class="muted">Configura la tienda en tu hosting en un minuto.</p>

    <h2>Requisitos</h2>
    <ul class="req">
        <?php foreach ($checks as $label => $ok): ?>
            <li class="<?= $ok ? 'ok' : 'ko' ?>"><?= $ok ? '✔' : '✘' ?> <?= h($label) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php if (!$requirementsOk): ?>
        <p class="warn">Corrige los requisitos marcados en rojo y recarga la página. La versión de PHP se cambia en cPanel → <em>MultiPHP Manager</em>; los permisos, en el Administrador de archivos (carpetas a 755).</p>
    <?php else: ?>
        <form method="post" autocomplete="off">
            <?php foreach ($errors as $err): ?><div class="error"><?= h($err) ?></div><?php endforeach; ?>

            <h2>Base de datos</h2>
            <p class="muted">Créala antes en cPanel → <em>Asistente de bases de datos MySQL</em> (con todos los privilegios para el usuario). En cPanel los nombres llevan tu usuario delante, por ejemplo <code>usuario_tienda</code>.</p>
            <label>Servidor <input type="text" name="db_host" value="<?= h($f['db_host']) ?>" required></label>
            <label>Nombre de la base de datos <input type="text" name="db_name" value="<?= h($f['db_name']) ?>" required></label>
            <div class="row">
                <label>Usuario <input type="text" name="db_user" value="<?= h($f['db_user']) ?>" required></label>
                <label>Contraseña <input type="password" name="db_pass" value=""></label>
            </div>

            <h2>Tienda</h2>
            <div class="row">
                <label>Nombre <input type="text" name="site_name" value="<?= h($f['site_name']) ?>"></label>
                <label>Email de la tienda <input type="email" name="site_email" value="<?= h($f['site_email']) ?>" placeholder="hola@tudominio.com"></label>
            </div>
            <label>URL de la tienda <input type="url" name="app_url" value="<?= h($f['app_url']) ?>" required></label>
            <label class="check"><input type="checkbox" name="demo" value="1" <?= $f['demo'] === '1' ? 'checked' : '' ?>> Cargar productos de ejemplo</label>

            <h2>Administrador</h2>
            <div class="row">
                <label>Nombre <input type="text" name="admin_name" value="<?= h($f['admin_name']) ?>"></label>
                <label>Email (para entrar) <input type="email" name="admin_email" value="<?= h($f['admin_email']) ?>" required></label>
            </div>
            <label>Contraseña <small>(mín. 8 caracteres)</small> <input type="password" name="admin_pass" required minlength="8"></label>

            <p><button type="submit">Instalar tienda</button></p>
        </form>
    <?php endif; ?>
<?php endif; ?>
</div>
</body>
</html>
