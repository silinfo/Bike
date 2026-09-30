<?php /** @var array $order @var array $items */ ?>
<p style="font-size:15px;line-height:1.6;margin:0 0 12px;">Hola <?= e(explode(' ', $order['customer_name'])[0]) ?>,</p>
<?php if ($order['payment_method'] === 'store'): ?>
<p style="font-size:15px;line-height:1.6;margin:0 0 12px;">Tu pedido <strong><?= e($order['reference']) ?></strong> está listo. Puedes recogerlo en <strong><?= e(config('site.address')) ?></strong> de lunes a viernes de 10:00 a 20:00 y los sábados de 10:00 a 14:00.</p>
<?php else: ?>
<p style="font-size:15px;line-height:1.6;margin:0 0 12px;">Tu pedido <strong><?= e($order['reference']) ?></strong> ya ha salido de nuestro taller y llegará en los próximos días.</p>
<?php if ($order['tracking_number']): ?>
<div style="background:#f5f4f0;border-left:4px solid #d7263d;padding:16px 20px;margin:20px 0;font-size:14px;">
    Número de seguimiento: <strong><?= e($order['tracking_number']) ?></strong>
</div>
<?php endif; ?>
<?php if ($order['payment_method'] === 'cod'): ?>
<p style="font-size:14px;line-height:1.6;">Recuerda tener preparado el importe de <strong><?= money($order['total']) ?></strong> para pagar al repartidor.</p>
<?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/_order-summary.php'; ?>
