<?php
require __DIR__ . '/_bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$order = db_one('SELECT * FROM orders WHERE id = ?', [$id]);
if (!$order) { flash('error', 'Pedido no encontrado.'); redirect('admin/pedidos.php'); }
$items = db_all('SELECT * FROM order_items WHERE order_id = ?', [$id]);
$statuses = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $new = $_POST['status'] ?? '';
    if (in_array($new, $statuses, true) && $new !== $order['status']) {
        $pdo = db();
        $pdo->beginTransaction();
        // Cancelar devuelve el stock; reactivar un pedido cancelado lo vuelve a descontar
        $delta = match (true) {
            $new === 'cancelled' => 1,
            $order['status'] === 'cancelled' => -1,
            default => 0,
        };
        if ($delta) {
            foreach ($items as $it) {
                if ($it['variant_id']) db_exec('UPDATE product_variants SET stock = GREATEST(0, stock + ?) WHERE id = ?', [$delta * $it['quantity'], $it['variant_id']]);
                elseif ($it['product_id']) db_exec('UPDATE products SET stock = GREATEST(0, stock + ?) WHERE id = ?', [$delta * $it['quantity'], $it['product_id']]);
            }
        }
        db_exec('UPDATE orders SET status = ? WHERE id = ?', [$new, $id]);
        $pdo->commit();
        flash('success', 'Estado actualizado a «' . order_status_label($new) . '».' . ($delta === 1 ? ' Stock repuesto.' : ''));
    }
    redirect('admin/pedido.php?id=' . $id);
}

$section = 'pedidos';
$pageTitle = 'Pedido ' . $order['reference'];
require __DIR__ . '/_header.php';
?>
<div class="page-head">
    <h1>Pedido <?= e($order['reference']) ?></h1>
    <span class="status status-<?= e($order['status']) ?>"><?= e(order_status_label($order['status'])) ?></span>
</div>

<div class="grid-main-aside">
    <section class="card">
        <h2>Productos</h2>
        <table class="table">
            <thead><tr><th>Producto</th><th>Talla</th><th>Precio</th><th>Cant.</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= $it['product_id'] ? '<a href="' . url('admin/producto.php?id=' . $it['product_id']) . '">' . e($it['product_name']) . '</a>' : e($it['product_name']) ?></td>
                    <td><?= e($it['variant_label'] ?? '—') ?></td>
                    <td><?= money($it['unit_price']) ?></td>
                    <td><?= (int)$it['quantity'] ?></td>
                    <td><?= money($it['unit_price'] * $it['quantity']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="4">Subtotal</td><td><?= money($order['subtotal']) ?></td></tr>
                <tr><td colspan="4">Envío</td><td><?= money($order['shipping']) ?></td></tr>
                <tr><th colspan="4">Total</th><th><?= money($order['total']) ?></th></tr>
            </tfoot>
        </table>
        <?php if ($order['notes']): ?><h3>Notas del cliente</h3><p><?= nl2br(e($order['notes'])) ?></p><?php endif; ?>
    </section>

    <div>
        <section class="card">
            <h2>Estado</h2>
            <form method="post">
                <?= csrf_field() ?>
                <select name="status">
                    <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= e(order_status_label($s)) ?></option><?php endforeach; ?>
                </select>
                <button class="btn btn-accent btn-block" type="submit">Actualizar</button>
            </form>
        </section>
        <section class="card">
            <h2>Cliente</h2>
            <p><strong><?= e($order['customer_name']) ?></strong><br>
               <a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a><br>
               <a href="tel:<?= e($order['phone']) ?>"><?= e($order['phone']) ?></a></p>
            <h3>Entrega</h3>
            <?php if ($order['payment_method'] === 'store'): ?>
                <p>Recogida en tienda</p>
            <?php else: ?>
                <p><?= e($order['address']) ?><br><?= e($order['postal_code']) ?> <?= e($order['city']) ?><br><?= e($order['province']) ?></p>
            <?php endif; ?>
            <h3>Pago</h3>
            <p><?= e(payment_method_label($order['payment_method'])) ?></p>
            <p class="muted">Realizado el <?= date('d/m/Y \a \l\a\s H:i', strtotime($order['created_at'])) ?></p>
        </section>
    </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
