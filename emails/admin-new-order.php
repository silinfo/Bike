<?php /** @var array $order @var array $items */ ?>
<p style="font-size:15px;line-height:1.6;margin:0 0 12px;">
    <strong><?= e($order['customer_name']) ?></strong> · <a href="mailto:<?= e($order['email']) ?>" style="color:#d7263d;"><?= e($order['email']) ?></a> · <?= e($order['phone']) ?>
</p>
<p style="font-size:15px;line-height:1.6;margin:0 0 12px;">
    Estado: <strong><?= e(order_status_label($order['status'])) ?></strong>
    <?php if ($order['payment_method'] === 'card'): ?>(cobrado con Stripe)<?php endif; ?>
</p>
<?php if ($order['notes']): ?>
<div style="background:#fff4db;padding:12px 16px;margin:16px 0;font-size:14px;"><strong>Notas del cliente:</strong><br><?= nl2br(e($order['notes'])) ?></div>
<?php endif; ?>
<p style="margin:20px 0;"><a href="<?= e(absolute_url('admin/pedido.php?id=' . $order['id'])) ?>" style="display:inline-block;background:#d7263d;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:4px;font-weight:bold;text-transform:uppercase;font-size:14px;">Ver pedido en el panel</a></p>
<?php require __DIR__ . '/_order-summary.php'; ?>
