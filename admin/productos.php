<?php
require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'toggle') {
        db_exec('UPDATE products SET active = 1 - active WHERE id = ?', [$id]);
        flash('success', 'Visibilidad actualizada.');
    } elseif (($_POST['action'] ?? '') === 'delete') {
        // Si tiene pedidos no se borra físicamente: se oculta
        $hasOrders = db_one('SELECT 1 FROM order_items WHERE product_id = ? LIMIT 1', [$id]);
        if ($hasOrders) {
            db_exec('UPDATE products SET active = 0 WHERE id = ?', [$id]);
            flash('info', 'El producto tiene pedidos asociados: se ha ocultado en lugar de borrarse.');
        } else {
            $img = db_one('SELECT image FROM products WHERE id = ?', [$id])['image'] ?? null;
            db_exec('DELETE FROM products WHERE id = ?', [$id]);
            if ($img && str_starts_with($img, 'uploads/')) @unlink(__DIR__ . '/../' . $img);
            flash('success', 'Producto eliminado.');
        }
    }
    redirect('admin/productos.php' . (!empty($_GET) ? '?' . http_build_query($_GET) : ''));
}

$type = in_array($_GET['tipo'] ?? '', ['bike', 'accessory'], true) ? $_GET['tipo'] : null;
$q = trim((string)($_GET['q'] ?? ''));
$where = ['1=1']; $params = [];
if ($type) { $where[] = 'c.type = ?'; $params[] = $type; }
if ($q !== '') { $where[] = 'p.name LIKE ?'; $params[] = "%$q%"; }
$products = db_all(PRODUCT_SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY c.type, c.sort_order, p.name', $params);

$section = 'productos';
$pageTitle = 'Productos';
require __DIR__ . '/_header.php';
?>
<div class="page-head">
    <h1>Productos</h1>
    <a href="<?= url('admin/producto.php') ?>" class="btn btn-accent">+ Nuevo producto</a>
</div>

<form class="toolbar" method="get">
    <select name="tipo" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="bike" <?= $type === 'bike' ? 'selected' : '' ?>>Bicicletas</option>
        <option value="accessory" <?= $type === 'accessory' ? 'selected' : '' ?>>Accesorios</option>
    </select>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre">
    <button class="btn" type="submit">Buscar</button>
</form>

<div class="card">
<table class="table">
    <thead><tr><th></th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): $stock = product_stock($p); ?>
        <tr class="<?= $p['active'] ? '' : 'is-off' ?>">
            <td><img class="thumb" src="<?= product_image($p['image']) ?>" alt=""></td>
            <td><a href="<?= url('admin/producto.php?id=' . $p['id']) ?>"><strong><?= e($p['name']) ?></strong></a><?= $p['featured'] ? ' <span class="tag">Destacado</span>' : '' ?></td>
            <td><?= e($p['category_name']) ?></td>
            <td><?= money($p['price']) ?></td>
            <td class="<?= $stock <= 0 ? 'danger' : ($stock <= 2 ? 'warn' : '') ?>"><?= $stock ?></td>
            <td>
                <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button class="link" name="action" value="toggle"><?= $p['active'] ? 'Visible' : 'Oculto' ?></button></form>
            </td>
            <td class="actions">
                <a href="<?= url('admin/producto.php?id=' . $p['id']) ?>">Editar</a>
                <form method="post" class="inline" onsubmit="return confirm('¿Eliminar «<?= e(addslashes($p['name'])) ?>»?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button class="link danger" name="action" value="delete">Eliminar</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
