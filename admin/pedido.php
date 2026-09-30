<?php
require __DIR__ . '/_bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$order = db_one('SELECT * FROM orders WHERE id = ?', [$id]);
if (!$order) { flash('error', 'Pedido no encontrado.'); redirect('admin/pedidos.php'); }
$items = db_all('SELECT * FROM order_items WHERE order_id = ?', [$id]);
$statuses = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $new = (string)($_POST['status'] ?? '');
    $tracking = trim((string)($_POST['tracking_number'] ?? ''));
    $notify = !empty($_POST['notify']);

    if ($tracking !== (string)$order['tracking_number']) {
        db_exec('UPDATE orders SET tracking_number = ? WHERE id = ?', [$tracking ?: null, $id]);
    }
    if (in_array($new, $statuses, true) && $new !== $order['status']) {
        order_transition($id, $new, $order['status'], $notify);
        $msg = 'Estado actualizado a «' . order_status_label($new) . '».';
        if ($new === 'cancelled') $msg .= ' Stock repuesto.';
        if ($notify && in_array($new, ['paid', 'shipped', 'cancelled'], true)) $msg .= ' Email enviado al cliente.';
        if ($new === 'cancelled' && $order['payment_method'] === 'card' && $order['paid_at']) $msg .= ' Recuerda hacer el reembolso desde el panel de Stripe.';
        flash('success', $msg);
    } elseif ($tracking !== (string)$order['tracking_number']) {
        flash('success', 'Número de seguimiento guardado.');
    }
    redirect('admin/pedido.php?id=' . $id);
}

// Pedido con tarjeta pendiente: comprobamos en Stripe por si el webhook no llegó
if ($order['payment_method'] === 'card' && $order['status'] === 'pending') {
    stripe_sync_order($order);
    $order = order_find($id);
}
$stripeDashboard = str_starts_with((string)config('stripe.secret_key'), 'sk_test_') ? 'https://dashboard.stripe.com/test/payments/' : 'https://dashboard.stripe.com/payments/';

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
                <label>Estado
                    <select name="status">
                        <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= e(order_status_label($s)) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <?php if ($order['payment_method'] !== 'store'): ?>
                    <label>Nº de seguimiento <small class="muted">(se incluye en el email de envío)</small>
                        <input type="text" name="tracking_number" value="<?= e($order['tracking_number']) ?>" maxlength="80">
                    </label>
                <?php endif; ?>
                <label class="check"><input type="checkbox" name="notify" value="1" checked> Avisar al cliente por email</label>
                <button class="btn btn-accent btn-block" type="submit">Actualizar</button>
            </form>
            <?php if ($order['payment_method'] === 'card' && $order['status'] === 'pending'): ?>
                <p class="muted">Esperando el pago en Stripe. Si no se completa, el pedido se cancelará solo y se repondrá el stock.</p>
            <?php endif; ?>
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
            <p><?= e(payment_method_label($order['payment_method'])) ?>
                <?php if ($order['paid_at']): ?><br><small class="muted">Cobrado el <?= date('d/m/Y H:i', strtotime($order['paid_at'])) ?></small><?php endif; ?>
                <?php if ($order['stripe_payment_intent']): ?><br><a href="<?= e($stripeDashboard . $order['stripe_payment_intent']) ?>" target="_blank" rel="noopener">Ver pago en Stripe ↗</a><?php endif; ?>
            </p>
            <p class="muted">Realizado el <?= date('d/m/Y \a \l\a\s H:i', strtotime($order['created_at'])) ?></p>
        </section>
    </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
