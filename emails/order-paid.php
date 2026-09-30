<?php /** @var array $order @var array $items */ ?>
<p style="font-size:15px;line-height:1.6;margin:0 0 12px;">Hola <?= e(explode(' ', $order['customer_name'])[0]) ?>,</p>
<?php if ($order['payment_method'] === 'card'): ?>
<p style="font-size:15px;line-height:1.6;margin:0 0 12px;">Tu pago con tarjeta se ha completado correctamente y tu pedido <strong><?= e($order['reference']) ?></strong> está confirmado. Ya lo estamos preparando en el taller.</p>
<?php else: ?>
<p style="font-size:15px;line-height:1.6;margin:0 0 12px;">Hemos recibido el pago de tu pedido <strong><?= e($order['reference']) ?></strong>. Ya lo estamos preparando en el taller.</p>
<?php endif; ?>
<p style="font-size:15px;line-height:1.6;margin:0 0 12px;">Te enviaremos otro email cuando <?= $order['payment_method'] === 'store' ? 'esté listo para recoger' : 'salga hacia tu casa' ?>.</p>
<?php require __DIR__ . '/_order-summary.php'; ?>
