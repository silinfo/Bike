<?php /** @var array $order @var array $items */ ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;border-collapse:collapse;font-size:14px;">
    <tr>
        <th align="left" style="padding:8px 0;border-bottom:2px solid #0e0e0e;text-transform:uppercase;font-size:12px;">Producto</th>
        <th align="center" style="padding:8px 0;border-bottom:2px solid #0e0e0e;text-transform:uppercase;font-size:12px;">Cant.</th>
        <th align="right" style="padding:8px 0;border-bottom:2px solid #0e0e0e;text-transform:uppercase;font-size:12px;">Total</th>
    </tr>
    <?php foreach ($items as $it): ?>
    <tr>
        <td style="padding:10px 0;border-bottom:1px solid #e4e2dd;"><?= e($it['product_name']) ?><?php if ($it['variant_label']): ?><br><span style="color:#6d6d6d;font-size:12px;">Talla <?= e($it['variant_label']) ?></span><?php endif; ?></td>
        <td align="center" style="padding:10px 0;border-bottom:1px solid #e4e2dd;"><?= (int)$it['quantity'] ?></td>
        <td align="right" style="padding:10px 0;border-bottom:1px solid #e4e2dd;"><?= money($it['unit_price'] * $it['quantity']) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr><td colspan="2" style="padding:8px 0 2px;color:#6d6d6d;">Subtotal</td><td align="right" style="padding:8px 0 2px;"><?= money($order['subtotal']) ?></td></tr>
    <tr><td colspan="2" style="padding:2px 0;color:#6d6d6d;">Envío</td><td align="right" style="padding:2px 0;"><?= (float)$order['shipping'] > 0 ? money($order['shipping']) : 'Gratis' ?></td></tr>
    <tr><td colspan="2" style="padding:10px 0;font-size:16px;font-weight:bold;">Total <span style="font-weight:normal;font-size:12px;color:#6d6d6d;">(IVA incl.)</span></td><td align="right" style="padding:10px 0;font-size:16px;font-weight:bold;"><?= money($order['total']) ?></td></tr>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.6;">
    <tr>
        <td valign="top" width="50%" style="padding-right:12px;">
            <strong style="text-transform:uppercase;font-size:12px;color:#6d6d6d;">Entrega</strong><br>
            <?php if ($order['payment_method'] === 'store'): ?>
                Recogida en tienda<br><?= e(config('site.address')) ?>
            <?php else: ?>
                <?= e($order['customer_name']) ?><br><?= e($order['address']) ?><br><?= e($order['postal_code']) ?> <?= e($order['city']) ?> (<?= e($order['province']) ?>)
            <?php endif; ?>
        </td>
        <td valign="top" width="50%">
            <strong style="text-transform:uppercase;font-size:12px;color:#6d6d6d;">Pago</strong><br>
            <?= e(payment_method_label($order['payment_method'])) ?>
        </td>
    </tr>
</table>
