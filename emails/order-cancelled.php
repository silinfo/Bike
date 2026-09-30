<?php /** @var array $order @var array $items */ ?>
<p style="font-size:15px;line-height:1.6;margin:0 0 12px;">Hola <?= e(explode(' ', $order['customer_name'])[0]) ?>,</p>
<p style="font-size:15px;line-height:1.6;margin:0 0 12px;">Tu pedido <strong><?= e($order['reference']) ?></strong> ha sido cancelado.</p>
<?php if ($order['paid_at']): ?>
<p style="font-size:15px;line-height:1.6;margin:0 0 12px;">Como ya estaba pagado, te devolveremos el importe de <strong><?= money($order['total']) ?></strong> por el mismo medio de pago en un plazo de 5 a 10 días hábiles.</p>
<?php endif; ?>
<p style="font-size:15px;line-height:1.6;margin:0 0 12px;">Si no has solicitado esta cancelación o tienes cualquier duda, responde a este email y lo revisamos.</p>
<?php require __DIR__ . '/_order-summary.php'; ?>
