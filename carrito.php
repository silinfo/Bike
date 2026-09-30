<?php
require __DIR__ . '/includes/bootstrap.php';

$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $error = null;

    if ($action === 'add') {
        $error = cart_add((int)($_POST['product_id'] ?? 0), isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : null, (int)($_POST['qty'] ?? 1));
        $message = $error ?? 'Producto añadido al carrito.';
    } elseif ($action === 'update' && isset($_POST['remove'])) {
        cart_update((string)$_POST['remove'], 0);
        $message = 'Producto eliminado.';
    } elseif ($action === 'update') {
        foreach ((array)($_POST['qty'] ?? []) as $key => $qty) cart_update((string)$key, (int)$qty);
        cart_items(); // ajusta al stock
        $message = 'Carrito actualizado.';
    } else {
        $error = $message = 'Acción no válida.';
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => $error === null, 'message' => $message, 'count' => cart_count()]);
        exit;
    }
    flash($error ? 'error' : 'success', $message);
    if ($action === 'add' && $error) redirect_back('carrito.php');
    redirect('carrito.php');
}

$items = cart_items();
$totals = cart_totals($items);
$freeFrom = (float)config('shop.free_shipping_from');

$pageTitle = 'Carrito';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero-sm">
    <div class="container"><h1>Tu carrito</h1></div>
</section>

<section class="section">
    <div class="container">
        <?php if (!$items): ?>
            <div class="empty-state">
                <p>Tu carrito está vacío.</p>
                <a href="<?= url('tienda.php') ?>" class="btn btn-dark">Ir a la tienda</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <form method="post" class="cart-items">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">
                    <?php foreach ($items as $it): ?>
                        <div class="cart-row">
                            <a href="<?= url('producto.php?slug=' . urlencode($it['slug'])) ?>" class="cart-thumb"><img src="<?= product_image($it['image']) ?>" alt=""></a>
                            <div class="cart-info">
                                <a href="<?= url('producto.php?slug=' . urlencode($it['slug'])) ?>"><strong><?= e($it['name']) ?></strong></a>
                                <?php if ($it['variant']): ?><span>Talla: <?= e($it['variant']) ?></span><?php endif; ?>
                                <span><?= money($it['price']) ?></span>
                            </div>
                            <input class="cart-qty" type="number" name="qty[<?= e($it['key']) ?>]" value="<?= $it['qty'] ?>" min="0" max="<?= $it['stock'] ?>" aria-label="Cantidad" data-autosubmit>
                            <strong class="cart-line"><?= money($it['line_total']) ?></strong>
                            <button class="cart-remove" type="submit" name="remove" value="<?= e($it['key']) ?>" aria-label="Eliminar">×</button>
                        </div>
                    <?php endforeach; ?>
                    <div class="cart-actions">
                        <a href="<?= url('tienda.php') ?>" class="link-arrow link-back">Seguir comprando</a>
                        <button class="btn btn-outline" type="submit">Actualizar carrito</button>
                    </div>
                </form>

                <aside class="summary">
                    <h3>Resumen</h3>
                    <?php if ($totals['subtotal'] < $freeFrom): ?>
                        <div class="free-ship">
                            <span>Te faltan <strong><?= money($freeFrom - $totals['subtotal']) ?></strong> para el envío gratis</span>
                            <div class="progress"><span style="width: <?= min(100, round($totals['subtotal'] / $freeFrom * 100)) ?>%"></span></div>
                        </div>
                    <?php endif; ?>
                    <dl>
                        <div><dt>Subtotal</dt><dd><?= money($totals['subtotal']) ?></dd></div>
                        <div><dt>Envío</dt><dd><?= $totals['shipping'] ? money($totals['shipping']) : 'Gratis' ?></dd></div>
                        <div class="summary-total"><dt>Total</dt><dd><?= money($totals['total']) ?></dd></div>
                    </dl>
                    <p class="tax-note">IVA incluido</p>
                    <a href="<?= url('checkout.php') ?>" class="btn btn-accent btn-lg btn-block">Finalizar compra</a>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
