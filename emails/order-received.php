<?php /** @var array $order @var array $items */ ?>
<p style="font-size:15px;line-height:1.6;margin:0 0 12px;">Hola <?= e(explode(' ', $order['customer_name'])[0]) ?>,</p>
<p style="font-size:15px;line-height:1.6;margin:0 0 12px;">Hemos recibido tu pedido <strong><?= e($order['reference']) ?></strong>. Estos son los detalles:</p>

<?php if ($order['payment_method'] === 'transfer'): ?>
<div style="background:#f5f4f0;border-left:4px solid #d7263d;padding:16px 20px;margin:20px 0;font-size:14px;line-height:1.7;">
    <strong style="text-transform:uppercase;">Datos para la transferencia</strong><br>
    Titular: <strong><?= e(config('shop.bank_holder')) ?></strong><br>
    IBAN: <strong><?= e(config('shop.bank_iban')) ?></strong><br>
    Importe: <strong><?= money($order['total']) ?></strong><br>
    Concepto: <strong><?= e($order['reference']) ?></strong><br>
    <span style="color:#6d6d6d;">Prepararemos tu pedido en cuanto recibamos el pago.</span>
</div>
<?php elseif ($order['payment_method'] === 'cod'): ?>
<p style="font-size:14px;line-height:1.6;">Pagarás <strong><?= money($order['total']) ?></strong> al recibir el pedido. Te avisaremos cuando salga de nuestro taller.</p>
<?php elseif ($order['payment_method'] === 'store'): ?>
<p style="font-size:14px;line-height:1.6;">Te escribiremos cuando tu pedido esté listo para recoger en <strong><?= e(config('site.address')) ?></strong>. Pagarás en tienda.</p>
<?php endif; ?>

<?php require __DIR__ . '/_order-summary.php'; ?>
