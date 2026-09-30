<?php
require __DIR__ . '/includes/bootstrap.php';

// El cliente ha vuelto desde Stripe sin pagar
$ref = (string)($_GET['ref'] ?? '');
if ($ref === '' || ($_SESSION['last_order'] ?? '') !== $ref) redirect('carrito.php');

$order = order_find_by_reference($ref);
if (!$order || $order['payment_method'] !== 'card') redirect('carrito.php');

if (stripe_cancel_checkout($order)) {
    // Pedido cancelado y stock liberado: devolvemos los productos al carrito
    order_restore_cart((int)$order['id']);
    unset($_SESSION['last_order']);
    flash('info', 'Has cancelado el pago. Tus productos siguen en el carrito.');
    redirect('carrito.php');
}

// En realidad el pago sí se completó
redirect('pedido.php?ref=' . urlencode($ref));
