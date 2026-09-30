<?php
require __DIR__ . '/includes/bootstrap.php';

$type = in_array($_GET['tipo'] ?? '', ['bike', 'accessory'], true) ? $_GET['tipo'] : null;
$catSlug = is_string($_GET['cat'] ?? null) ? $_GET['cat'] : null;
$q = trim((string)($_GET['q'] ?? ''));
$sort = $_GET['orden'] ?? 'destacados';
$maxPrice = isset($_GET['max']) && is_numeric($_GET['max']) ? (float)$_GET['max'] : null;
$onlyStock = !empty($_GET['stock']);

$category = null;
if ($catSlug) {
    $category = db_one('SELECT * FROM categories WHERE slug = ?', [$catSlug]);
    if ($category) $type = $category['type'];
}

$where = ['p.active = 1'];
$params = [];
if ($category) { $where[] = 'p.category_id = ?'; $params[] = $category['id']; }
elseif ($type) { $where[] = 'c.type = ?'; $params[] = $type; }
if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.short_desc LIKE ? OR c.name LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($maxPrice) { $where[] = 'p.price <= ?'; $params[] = $maxPrice; }

$orderBy = match ($sort) {
    'precio-asc'  => 'p.price ASC',
    'precio-desc' => 'p.price DESC',
    'nuevos'      => 'p.created_at DESC, p.id DESC',
    default       => 'p.featured DESC, c.sort_order, p.id',
};

$products = db_all(PRODUCT_SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $orderBy, $params);
if ($onlyStock) $products = array_values(array_filter($products, fn($p) => product_stock($p) > 0));

$title = $category['name'] ?? match ($type) { 'bike' => 'Bicicletas', 'accessory' => 'Accesorios', default => 'Tienda' };
if ($q !== '') $title = 'Resultados para “' . $q . '”';
$intro = $category['description'] ?? match ($type) {
    'bike'      => 'Carretera, gravel, montaña, urbanas y eléctricas. Encuentra la tuya.',
    'accessory' => 'Todo lo que necesitas para rodar seguro y cómodo.',
    default     => 'Bicicletas y accesorios seleccionados por nuestro equipo.',
};
$sidebarCats = $type ? categories($type) : categories();

// Conserva filtros al cambiar uno
function shop_url(array $changes): string
{
    $params = array_filter(array_merge($_GET, $changes), fn($v) => $v !== null && $v !== '');
    return url('tienda.php' . ($params ? '?' . http_build_query($params) : ''));
}

$pageTitle = $title;
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs"><a href="<?= url() ?>">Inicio</a> / <a href="<?= url('tienda.php') ?>">Tienda</a>
            <?php if ($category): ?> / <a href="<?= url('tienda.php?tipo=' . $category['type']) ?>"><?= $category['type'] === 'bike' ? 'Bicicletas' : 'Accesorios' ?></a><?php endif; ?>
        </nav>
        <h1><?= e($title) ?></h1>
        <p><?= e($intro) ?></p>
    </div>
</section>

<section class="section shop">
    <div class="container shop-layout">
        <aside class="shop-sidebar" id="shopSidebar">
            <form method="get" action="<?= url('tienda.php') ?>" class="filters">
                <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
                <?php if ($catSlug): ?><input type="hidden" name="cat" value="<?= e($catSlug) ?>"><?php elseif ($type): ?><input type="hidden" name="tipo" value="<?= e($type) ?>"><?php endif; ?>

                <div class="filter-group">
                    <h4>Categorías</h4>
                    <ul class="filter-list">
                        <li><a href="<?= $type ? url('tienda.php?tipo=' . $type) : url('tienda.php') ?>" class="<?= !$category ? 'active' : '' ?>">Todas</a></li>
                        <?php foreach ($sidebarCats as $c): ?>
                            <li><a href="<?= url('tienda.php?cat=' . urlencode($c['slug'])) ?>" class="<?= $category && $category['id'] == $c['id'] ? 'active' : '' ?>"><?= e($c['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="filter-group">
                    <h4>Precio máximo</h4>
                    <?php $rangeMax = $type === 'accessory' ? 1500 : 6000; ?>
                    <input type="range" name="max" min="50" max="<?= $rangeMax ?>" step="50" value="<?= e((string)($maxPrice ?: $rangeMax)) ?>" data-range-output="maxOut">
                    <output id="maxOut"><?= money($maxPrice ?: $rangeMax) ?></output>
                </div>

                <div class="filter-group">
                    <label class="check"><input type="checkbox" name="stock" value="1" <?= $onlyStock ? 'checked' : '' ?>> Solo con stock</label>
                </div>

                <input type="hidden" name="orden" value="<?= e($sort) ?>">
                <button class="btn btn-dark btn-block" type="submit">Aplicar filtros</button>
            </form>
        </aside>

        <div class="shop-main">
            <div class="shop-toolbar">
                <span><?= count($products) ?> productos</span>
                <button class="btn btn-outline btn-sm filters-toggle" id="filtersToggle" type="button">Filtros</button>
                <label class="sort">Ordenar:
                    <select onchange="location.href=this.value">
                        <?php foreach (['destacados' => 'Destacados', 'nuevos' => 'Novedades', 'precio-asc' => 'Precio: menor a mayor', 'precio-desc' => 'Precio: mayor a menor'] as $k => $label): ?>
                            <option value="<?= e(shop_url(['orden' => $k])) ?>" <?= $sort === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <?php if ($products): ?>
                <div class="product-grid product-grid-3">
                    <?php foreach ($products as $p) include __DIR__ . '/includes/product-card.php'; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No hemos encontrado productos con esos filtros.</p>
                    <a href="<?= url('tienda.php') ?>" class="btn btn-dark">Ver todo el catálogo</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
