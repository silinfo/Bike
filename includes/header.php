<?php
/** @var string $pageTitle */
/** @var string $bodyClass */
$pageTitle = isset($pageTitle) ? $pageTitle . ' · ' . config('site.name') : config('site.name') . ' · ' . config('site.tagline');
$bodyClass = $bodyClass ?? '';
$current = basename($_SERVER['SCRIPT_NAME'], '.php');
$currentType = $_GET['tipo'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription ?? config('site.tagline')) ?>">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><circle cx='25' cy='65' r='20' fill='none' stroke='%23111' stroke-width='8'/><circle cx='75' cy='65' r='20' fill='none' stroke='%23111' stroke-width='8'/><path d='M25 65 L45 30 L70 30 L75 65 M45 30 L55 65' stroke='%23d7263d' stroke-width='8' fill='none'/></svg>">
</head>
<body class="<?= e($bodyClass) ?>">

<div class="announcement">
    Envío gratis en pedidos superiores a <?= money(config('shop.free_shipping_from')) ?> · Prueba tu bici en tienda
</div>

<header class="site-header" id="siteHeader">
    <div class="container header-inner">
        <button class="nav-toggle" id="navToggle" aria-label="Abrir menú" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <a href="<?= url() ?>" class="logo"><?= e(config('site.name')) ?></a>

        <nav class="main-nav" id="mainNav" aria-label="Principal">
            <div class="nav-item has-mega">
                <a href="<?= url('tienda.php?tipo=bike') ?>" class="<?= $current === 'tienda' && $currentType === 'bike' ? 'active' : '' ?>">Bicicletas</a>
                <div class="mega">
                    <div class="container mega-inner">
                        <?php foreach (categories('bike') as $c): ?>
                            <a href="<?= url('tienda.php?cat=' . urlencode($c['slug'])) ?>" class="mega-link">
                                <strong><?= e($c['name']) ?></strong>
                                <span><?= e($c['description']) ?></span>
                            </a>
                        <?php endforeach; ?>
                        <a href="<?= url('tienda.php?tipo=bike') ?>" class="mega-link mega-all"><strong>Ver todas →</strong></a>
                    </div>
                </div>
            </div>
            <div class="nav-item has-mega">
                <a href="<?= url('tienda.php?tipo=accessory') ?>" class="<?= $current === 'tienda' && $currentType === 'accessory' ? 'active' : '' ?>">Accesorios</a>
                <div class="mega">
                    <div class="container mega-inner">
                        <?php foreach (categories('accessory') as $c): ?>
                            <a href="<?= url('tienda.php?cat=' . urlencode($c['slug'])) ?>" class="mega-link">
                                <strong><?= e($c['name']) ?></strong>
                                <span><?= e($c['description']) ?></span>
                            </a>
                        <?php endforeach; ?>
                        <a href="<?= url('tienda.php?tipo=accessory') ?>" class="mega-link mega-all"><strong>Ver todos →</strong></a>
                    </div>
                </div>
            </div>
            <div class="nav-item"><a href="<?= url('tienda.php') ?>" class="<?= $current === 'tienda' && !$currentType && empty($_GET['cat']) ? 'active' : '' ?>">Tienda</a></div>
            <div class="nav-item"><a href="<?= url('nosotros.php') ?>" class="<?= $current === 'nosotros' ? 'active' : '' ?>">Nosotros</a></div>
            <div class="nav-item"><a href="<?= url('contacto.php') ?>" class="<?= $current === 'contacto' ? 'active' : '' ?>">Contacto</a></div>
        </nav>

        <div class="header-actions">
            <button class="icon-btn" id="searchToggle" aria-label="Buscar">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </button>
            <a href="<?= url('carrito.php') ?>" class="icon-btn cart-btn" aria-label="Carrito">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 7h12l-1 13H7L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
                <span class="cart-count" id="cartCount" <?= cart_count() ? '' : 'hidden' ?>><?= cart_count() ?></span>
            </a>
        </div>
    </div>

    <form class="search-bar" id="searchBar" action="<?= url('tienda.php') ?>" method="get" role="search">
        <div class="container">
            <input type="search" name="q" placeholder="Buscar bicicletas, cascos, luces…" value="<?= e($_GET['q'] ?? '') ?>" aria-label="Buscar">
        </div>
    </form>
</header>

<?php $flashes = take_flashes(); if ($flashes): ?>
    <div class="container flashes">
        <?php foreach ($flashes as $f): ?>
            <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<main>
