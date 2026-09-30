<?php
require __DIR__ . '/_bootstrap.php';
if (admin_user()) redirect('admin/');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim((string)($_POST['email'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    $u = db_one('SELECT * FROM admin_users WHERE email = ?', [$email]);
    if ($u && password_verify($pass, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = ['id' => (int)$u['id'], 'name' => $u['name'], 'email' => $u['email']];
        redirect('admin/');
    }
    usleep(400000); // frena ataques de fuerza bruta
    $error = 'Email o contraseña incorrectos.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Acceso · Admin <?= e(config('site.name')) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
</head>
<body class="login-page">
    <form method="post" class="login-box">
        <?= csrf_field() ?>
        <div class="admin-logo"><?= e(config('site.name')) ?><span>admin</span></div>
        <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
        <label>Email <input type="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>"></label>
        <label>Contraseña <input type="password" name="password" required></label>
        <button class="btn btn-accent" type="submit">Entrar</button>
    </form>
</body>
</html>
