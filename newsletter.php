<?php
require __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('');
csrf_check();

$email = trim((string)($_POST['email'] ?? ''));
if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    db_exec('INSERT IGNORE INTO newsletter (email) VALUES (?)', [$email]);
    flash('success', '¡Suscripción completada! Pronto recibirás nuestras novedades.');
} else {
    flash('error', 'El email no es válido.');
}

redirect_back();
