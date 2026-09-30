<?php
require __DIR__ . '/includes/bootstrap.php';

$ref = (string)($_GET['ref'] ?? '');
// Solo quien acaba de hacer el pedido puede ver su resumen
if ($ref === '' || ($_SESSION['last_order'] ?? '') !== $ref) redirect('');

$order = order_find_by_reference($ref);
if (!$order) redirect('');

// Vuelta desde Stripe: confirmamos el pago sin esperar al webhook
if ($order['payment_method'] === 'card' && $order['status'] === 'pending') {
    stripe_sync_order($order);
    $order = order_find((int)$order['id']);
}
$items = order_items((int)$order['id']);
$awaitingCard = $order['payment_method'] === 'card' && $order['status'] === 'pending';

$pageTitle = 'Pedido confirmado';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container narrow order-done">
        <?php if ($order['status'] === 'cancelled'): ?>
            <div class="order-check order-check-off">×</div>
            <h1>Pedido cancelado</h1>
            <p>El pedido <strong><?= e($order['reference']) ?></strong> se ha cancelado y no se ha realizado ningún cobro.</p>
        <?php elseif ($awaitingCard): ?>
            <div class="order-check order-check-wait">…</div>
            <h1>Confirmando tu pago</h1>
            <p>Estamos esperando la confirmación de Stripe para el pedido <strong><?= e($order['reference']) ?></strong>. Esta página se actualizará sola en unos segundos.</p>
            <script>setTimeout(() => location.reload(), 4000);</script>
        <?php else: ?>
            <div class="order-check">✓</div>
            <h1><?= $order['payment_method'] === 'card' ? '¡Pago completado!' : '¡Gracias por tu pedido!' ?></h1>
            <p><?= $order['payment_method'] === 'card' ? 'Tu pago se ha realizado correctamente y tu pedido' : 'Hemos recibido tu pedido' ?> <strong><?= e($order['reference']) ?></strong>. Te hemos enviado la confirmación a <strong><?= e($order['email']) ?></strong>.</p>
        <?php endif; ?>

        <?php if ($order['payment_method'] === 'transfer' && $order['status'] === 'pending'): ?>
            <div class="notice">
                <h3>Datos para la transferencia</h3>
                <p>Titular: <strong><?= e(config('shop.bank_holder')) ?></strong><br>
                IBAN: <strong><?= e(config('shop.bank_iban')) ?></strong><br>
                Importe: <strong><?= money($order['total']) ?></strong><br>
                Concepto: <strong><?= e($order['reference']) ?></strong></p>
            </div>
        <?php elseif ($order['payment_method'] === 'store' && $order['status'] !== 'cancelled'): ?>
            <div class="notice">
                <h3>Recogida en tienda</h3>
                <p>Te avisaremos cuando tu pedido esté listo en <?= e(config('site.address')) ?>.</p>
            </div>
        <?php endif; ?>

        <table class="order-table">
            <thead><tr><th>Producto</th><th>Cant.</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= e($it['product_name']) ?><?= $it['variant_label'] ? ' · ' . e($it['variant_label']) : '' ?></td>
                    <td><?= (int)$it['quantity'] ?></td>
                    <td><?= money($it['unit_price'] * $it['quantity']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="2">Envío</td><td><?= $order['shipping'] > 0 ? money($order['shipping']) : 'Gratis' ?></td></tr>
                <tr><th colspan="2">Total</th><th><?= money($order['total']) ?></th></tr>
            </tfoot>
        </table>

        <a href="<?= url('tienda.php') ?>" class="btn btn-dark">Seguir comprando</a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
