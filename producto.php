<?php
require __DIR__ . '/includes/bootstrap.php';

$slug = (string)($_GET['slug'] ?? '');
$p = db_one(PRODUCT_SELECT . ' WHERE p.slug = ? AND p.active = 1', [$slug]);
if (!$p) {
    http_response_code(404);
    $pageTitle = 'Producto no encontrado';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container empty-state"><h1>Producto no encontrado</h1><a class="btn btn-dark" href="' . url('tienda.php') . '">Volver a la tienda</a></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$variants = db_all('SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order, id', [$p['id']]);
$specs = $p['specs'] ? (json_decode($p['specs'], true) ?: []) : [];
$stock = product_stock($p);
$onSale = $p['compare_price'] && (float)$p['compare_price'] > (float)$p['price'];
$related = db_all(PRODUCT_SELECT . ' WHERE p.active = 1 AND p.category_id = ? AND p.id <> ? ORDER BY p.featured DESC LIMIT 4', [$p['category_id'], $p['id']]);
if (count($related) < 4) {
    $related = array_merge($related, db_all(PRODUCT_SELECT . ' WHERE p.active = 1 AND c.type = ? AND p.category_id <> ? ORDER BY p.featured DESC, RAND() LIMIT ' . (4 - count($related)), [$p['type'], $p['category_id']]));
}
$sizeLabel = $p['type'] === 'bike' ? 'Talla de cuadro' : 'Talla';

$pageTitle = $p['name'];
$metaDescription = $p['short_desc'];
require __DIR__ . '/includes/header.php';
?>

<section class="section product-page">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="<?= url() ?>">Inicio</a> /
            <a href="<?= url('tienda.php?tipo=' . $p['type']) ?>"><?= $p['type'] === 'bike' ? 'Bicicletas' : 'Accesorios' ?></a> /
            <a href="<?= url('tienda.php?cat=' . urlencode($p['category_slug'])) ?>"><?= e($p['category_name']) ?></a>
        </nav>

        <div class="product-layout">
            <div class="product-gallery">
                <img src="<?= product_image($p['image']) ?>" alt="<?= e($p['name']) ?>">
                <?php if ($onSale): ?><span class="badge badge-sale">Oferta</span><?php endif; ?>
            </div>

            <div class="product-info">
                <span class="product-card-cat"><?= e($p['category_name']) ?></span>
                <h1><?= e($p['name']) ?></h1>
                <p class="lead"><?= e($p['short_desc']) ?></p>

                <div class="price price-lg">
                    <span><?= money($p['price']) ?></span>
                    <?php if ($onSale): ?><del><?= money($p['compare_price']) ?></del><?php endif; ?>
                </div>
                <p class="tax-note">IVA incluido. <?= (float)$p['price'] >= (float)config('shop.free_shipping_from') ? 'Envío gratuito.' : 'Envío ' . money(config('shop.shipping_cost')) . '.' ?></p>

                <form class="add-to-cart" id="addToCartForm" action="<?= url('carrito.php') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">

                    <?php if ($variants): ?>
                        <fieldset class="variant-picker">
                            <legend><?= e($sizeLabel) ?></legend>
                            <div class="variant-options">
                                <?php foreach ($variants as $i => $v): ?>
                                    <label class="variant <?= $v['stock'] <= 0 ? 'is-disabled' : '' ?>">
                                        <input type="radio" name="variant_id" value="<?= (int)$v['id'] ?>" <?= $v['stock'] <= 0 ? 'disabled' : '' ?> required>
                                        <span><?= e($v['label']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($p['type'] === 'bike'): ?>
                                <details class="size-guide">
                                    <summary>Guía de tallas</summary>
                                    <table>
                                        <tr><th>Talla</th><th>Altura ciclista</th></tr>
                                        <tr><td>S</td><td>160 – 170 cm</td></tr>
                                        <tr><td>M</td><td>170 – 178 cm</td></tr>
                                        <tr><td>L</td><td>178 – 186 cm</td></tr>
                                        <tr><td>XL</td><td>186 – 195 cm</td></tr>
                                    </table>
                                </details>
                            <?php endif; ?>
                        </fieldset>
                    <?php endif; ?>

                    <?php if ($stock > 0): ?>
                        <div class="buy-row">
                            <div class="qty">
                                <button type="button" data-qty="-1" aria-label="Menos">−</button>
                                <input type="number" name="qty" value="1" min="1" max="99" aria-label="Cantidad">
                                <button type="button" data-qty="1" aria-label="Más">+</button>
                            </div>
                            <button class="btn btn-accent btn-lg btn-grow" type="submit">Añadir al carrito</button>
                        </div>
                        <p class="stock-note <?= $stock <= 3 ? 'low' : '' ?>">
                            <?= $stock <= 3 ? '¡Últimas unidades!' : 'En stock · Envío en 24/48 h' ?>
                        </p>
                    <?php else: ?>
                        <button class="btn btn-lg btn-block" type="button" disabled>Agotado</button>
                        <p class="stock-note">Escríbenos y te avisamos cuando vuelva a estar disponible.</p>
                    <?php endif; ?>
                </form>

                <ul class="product-perks">
                    <li>✓ Montaje y ajuste en taller incluidos</li>
                    <li>✓ Devolución gratuita en 30 días</li>
                    <li>✓ Pago seguro</li>
                </ul>
            </div>
        </div>

        <div class="product-details">
            <div>
                <h2>Descripción</h2>
                <p><?= nl2br(e($p['description'])) ?></p>
            </div>
            <?php if ($specs): ?>
                <div>
                    <h2>Especificaciones</h2>
                    <dl class="specs">
                        <?php foreach ($specs as $k => $v): ?>
                            <div><dt><?= e((string)$k) ?></dt><dd><?= e((string)$v) ?></dd></div>
                        <?php endforeach; ?>
                    </dl>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($related): ?>
<section class="section section-alt">
    <div class="container">
        <div class="section-head"><h2>También te puede gustar</h2></div>
        <div class="product-grid">
            <?php foreach ($related as $p) include __DIR__ . '/includes/product-card.php'; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
