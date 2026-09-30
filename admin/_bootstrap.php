<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

function admin_user(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function require_admin(): array
{
    $u = admin_user();
    if (!$u) redirect('admin/login.php');
    return $u;
}

$admin = basename($_SERVER['SCRIPT_NAME']) === 'login.php' ? null : require_admin();
