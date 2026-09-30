<?php /** @var array $p */
$stock = product_stock($p);
$onSale = $p['compare_price'] && (float)$p['compare_price'] > (float)$p['price'];
?>
<article class="product-card">
    <a href="<?= url('producto.php?slug=' . urlencode($p['slug'])) ?>" class="product-card-media">
        <img src="<?= product_image($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
        <?php if ($onSale): ?>
            <span class="badge badge-sale">-<?= round(100 - ($p['price'] / $p['compare_price']) * 100) ?>%</span>
        <?php elseif ($stock <= 0): ?>
            <span class="badge badge-muted">Agotado</span>
        <?php elseif ($p['featured']): ?>
            <span class="badge">Destacado</span>
        <?php endif; ?>
    </a>
    <div class="product-card-body">
        <span class="product-card-cat"><?= e($p['category_name']) ?></span>
        <h3><a href="<?= url('producto.php?slug=' . urlencode($p['slug'])) ?>"><?= e($p['name']) ?></a></h3>
        <div class="price">
            <span><?= money($p['price']) ?></span>
            <?php if ($onSale): ?><del><?= money($p['compare_price']) ?></del><?php endif; ?>
        </div>
    </div>
</article>
