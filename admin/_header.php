<?php $section = $section ?? ''; ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e(($pageTitle ?? 'Panel') . ' · Admin ' . config('site.name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
</head>
<body>
<div class="admin">
    <aside class="admin-side">
        <a href="<?= url('admin/') ?>" class="admin-logo"><?= e(config('site.name')) ?><span>admin</span></a>
        <nav>
            <a href="<?= url('admin/') ?>" class="<?= $section === 'dashboard' ? 'active' : '' ?>">Resumen</a>
            <a href="<?= url('admin/productos.php') ?>" class="<?= $section === 'productos' ? 'active' : '' ?>">Productos</a>
            <a href="<?= url('admin/pedidos.php') ?>" class="<?= $section === 'pedidos' ? 'active' : '' ?>">Pedidos</a>
            <a href="<?= url('admin/mensajes.php') ?>" class="<?= $section === 'mensajes' ? 'active' : '' ?>">Mensajes</a>
        </nav>
        <div class="admin-side-foot">
            <a href="<?= url() ?>" target="_blank">Ver tienda ↗</a>
            <form method="post" action="<?= url('admin/logout.php') ?>"><?= csrf_field() ?><button type="submit">Cerrar sesión</button></form>
        </div>
    </aside>
    <main class="admin-main">
        <?php foreach (take_flashes() as $f): ?>
            <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
