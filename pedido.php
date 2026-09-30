<?php
require __DIR__ . '/includes/bootstrap.php';

$ref = (string)($_GET['ref'] ?? '');
// Solo quien acaba de hacer el pedido puede ver su resumen
if ($ref === '' || ($_SESSION['last_order'] ?? '') !== $ref) redirect('');

$order = db_one('SELECT * FROM orders WHERE reference = ?', [$ref]);
if (!$order) redirect('');
$items = db_all('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);

$pageTitle = 'Pedido confirmado';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container narrow order-done">
        <div class="order-check">✓</div>
        <h1>¡Gracias por tu pedido!</h1>
        <p>Hemos recibido tu pedido <strong><?= e($order['reference']) ?></strong>. Te enviaremos la confirmación a <strong><?= e($order['email']) ?></strong>.</p>

        <?php if ($order['payment_method'] === 'transfer'): ?>
            <div class="notice">
                <h3>Datos para la transferencia</h3>
                <p>IBAN: <strong>ES00 0000 0000 0000 0000 0000</strong><br>
                Importe: <strong><?= money($order['total']) ?></strong><br>
                Concepto: <strong><?= e($order['reference']) ?></strong></p>
            </div>
        <?php elseif ($order['payment_method'] === 'store'): ?>
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
