<?php
require __DIR__ . '/_bootstrap.php';

$stats = db_one("SELECT
    (SELECT COUNT(*) FROM orders WHERE status <> 'cancelled') AS orders,
    (SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ('paid','shipped','completed')) AS revenue,
    (SELECT COUNT(*) FROM orders WHERE status = 'pending') AS pending,
    (SELECT COUNT(*) FROM products WHERE active = 1) AS products");
$recent = db_all('SELECT * FROM orders ORDER BY created_at DESC LIMIT 8');
$lowStock = db_all("SELECT p.id, p.name, v.label, COALESCE(v.stock, p.stock) AS stock
    FROM products p LEFT JOIN product_variants v ON v.product_id = p.id
    WHERE p.active = 1 AND COALESCE(v.stock, p.stock) <= 2
    ORDER BY stock, p.name LIMIT 10");

$section = 'dashboard';
$pageTitle = 'Resumen';
require __DIR__ . '/_header.php';
?>
<h1>Hola, <?= e($admin['name']) ?></h1>

<div class="stats">
    <div class="stat"><span>Ventas cobradas</span><strong><?= money($stats['revenue']) ?></strong></div>
    <div class="stat"><span>Pedidos</span><strong><?= (int)$stats['orders'] ?></strong></div>
    <div class="stat stat-warn"><span>Pendientes</span><strong><?= (int)$stats['pending'] ?></strong></div>
    <div class="stat"><span>Productos activos</span><strong><?= (int)$stats['products'] ?></strong></div>
</div>

<div class="grid-2">
    <section class="card">
        <div class="card-head"><h2>Últimos pedidos</h2><a href="<?= url('admin/pedidos.php') ?>">Ver todos</a></div>
        <?php if ($recent): ?>
        <table class="table">
            <thead><tr><th>Ref.</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $o): ?>
                <tr>
                    <td><a href="<?= url('admin/pedido.php?id=' . $o['id']) ?>"><?= e($o['reference']) ?></a></td>
                    <td><?= e($o['customer_name']) ?></td>
                    <td><?= money($o['total']) ?></td>
                    <td><span class="status status-<?= e($o['status']) ?>"><?= e(order_status_label($o['status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?><p class="muted">Aún no hay pedidos.</p><?php endif; ?>
    </section>

    <section class="card">
        <div class="card-head"><h2>Stock bajo</h2><a href="<?= url('admin/productos.php') ?>">Productos</a></div>
        <?php if ($lowStock): ?>
        <table class="table">
            <thead><tr><th>Producto</th><th>Talla</th><th>Stock</th></tr></thead>
            <tbody>
            <?php foreach ($lowStock as $s): ?>
                <tr>
                    <td><a href="<?= url('admin/producto.php?id=' . $s['id']) ?>"><?= e($s['name']) ?></a></td>
                    <td><?= e($s['label'] ?? '—') ?></td>
                    <td class="<?= $s['stock'] <= 0 ? 'danger' : '' ?>"><?= (int)$s['stock'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?><p class="muted">Todo el stock está en orden.</p><?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
