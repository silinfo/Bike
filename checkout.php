<?php
require __DIR__ . '/includes/bootstrap.php';

$items = cart_items();
if (!$items) {
    flash('info', 'Tu carrito está vacío.');
    redirect('carrito.php');
}

$fields = ['customer_name', 'email', 'phone', 'address', 'city', 'postal_code', 'province', 'notes', 'payment_method'];
$data = array_fill_keys($fields, '');
$data['payment_method'] = 'transfer';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($fields as $f) $data[$f] = trim((string)($_POST[$f] ?? ''));

    if (mb_strlen($data['customer_name']) < 3) $errors['customer_name'] = 'Indica tu nombre completo.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email no válido.';
    if (!preg_match('/^[0-9 +()-]{9,20}$/', $data['phone'])) $errors['phone'] = 'Teléfono no válido.';
    if (!in_array($data['payment_method'], ['transfer', 'cod', 'store'], true)) $errors['payment_method'] = 'Elige un método de pago.';
    if ($data['payment_method'] !== 'store') {
        if (mb_strlen($data['address']) < 5) $errors['address'] = 'Indica la dirección de envío.';
        if ($data['city'] === '') $errors['city'] = 'Indica la ciudad.';
        if (!preg_match('/^\d{5}$/', $data['postal_code'])) $errors['postal_code'] = 'Código postal de 5 dígitos.';
        if ($data['province'] === '') $errors['province'] = 'Indica la provincia.';
    } else {
        // Recogida en tienda: la dirección es la de la tienda
        $data['address'] = $data['address'] ?: 'Recogida en tienda';
        $data['city'] = $data['city'] ?: '-';
        $data['postal_code'] = $data['postal_code'] ?: '00000';
        $data['province'] = $data['province'] ?: '-';
    }
    if (empty($_POST['accept'])) $errors['accept'] = 'Debes aceptar las condiciones de compra.';

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $items = cart_items();
            $totals = cart_totals($items, $data['payment_method'] === 'store');

            // Descuenta stock de forma atómica; si falta stock se cancela todo
            foreach ($items as $it) {
                $affected = $it['variant_id']
                    ? db_exec('UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?', [$it['qty'], $it['variant_id'], $it['qty']])
                    : db_exec('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?', [$it['qty'], $it['product_id'], $it['qty']]);
                if ($affected !== 1) throw new RuntimeException('Sin stock suficiente de ' . $it['name'] . ($it['variant'] ? ' (' . $it['variant'] . ')' : '') . '.');
            }

            $reference = 'VX-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            db_exec('INSERT INTO orders (reference, customer_name, email, phone, address, city, postal_code, province, notes, payment_method, subtotal, shipping, total)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $reference, $data['customer_name'], $data['email'], $data['phone'], $data['address'], $data['city'],
                $data['postal_code'], $data['province'], $data['notes'] ?: null, $data['payment_method'],
                $totals['subtotal'], $totals['shipping'], $totals['total'],
            ]);
            $orderId = (int)$pdo->lastInsertId();
            foreach ($items as $it) {
                db_exec('INSERT INTO order_items (order_id, product_id, variant_id, product_name, variant_label, unit_price, quantity) VALUES (?,?,?,?,?,?,?)',
                    [$orderId, $it['product_id'], $it['variant_id'], $it['name'], $it['variant'], $it['price'], $it['qty']]);
            }
            $pdo->commit();

            cart_clear();
            $_SESSION['last_order'] = $reference;
            // Aquí puedes enviar el email de confirmación (mail() / PHPMailer)
            // o redirigir a la pasarela de pago (Stripe, Redsys, PayPal).
            redirect('pedido.php?ref=' . urlencode($reference));
        } catch (RuntimeException $ex) {
            $pdo->rollBack();
            flash('error', $ex->getMessage() . ' Revisa tu carrito.');
            redirect('carrito.php');
        }
    }
}

$totals = cart_totals($items, $data['payment_method'] === 'store');

function field_error(array $errors, string $f): string
{
    return isset($errors[$f]) ? '<small class="field-error">' . e($errors[$f]) . '</small>' : '';
}

$pageTitle = 'Finalizar compra';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero-sm">
    <div class="container"><h1>Finalizar compra</h1></div>
</section>

<section class="section">
    <div class="container">
        <form method="post" class="checkout-layout" novalidate id="checkoutForm">
            <?= csrf_field() ?>
            <div class="checkout-form">
                <fieldset>
                    <legend>1. Tus datos</legend>
                    <div class="form-grid">
                        <label class="span-2">Nombre y apellidos
                            <input type="text" name="customer_name" value="<?= e($data['customer_name']) ?>" required autocomplete="name">
                            <?= field_error($errors, 'customer_name') ?>
                        </label>
                        <label>Email
                            <input type="email" name="email" value="<?= e($data['email']) ?>" required autocomplete="email">
                            <?= field_error($errors, 'email') ?>
                        </label>
                        <label>Teléfono
                            <input type="tel" name="phone" value="<?= e($data['phone']) ?>" required autocomplete="tel">
                            <?= field_error($errors, 'phone') ?>
                        </label>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>2. Entrega y pago</legend>
                    <div class="pay-options">
                        <?php foreach (['transfer' => 'Pago por transferencia. Enviamos el pedido al recibir el pago.', 'cod' => 'Pagas al recibir el pedido (+0 €).', 'store' => 'Recoge y paga en nuestra tienda. Sin gastos de envío.'] as $m => $desc): ?>
                            <label class="pay-option">
                                <input type="radio" name="payment_method" value="<?= $m ?>" <?= $data['payment_method'] === $m ? 'checked' : '' ?>>
                                <span><strong><?= e(payment_method_label($m)) ?></strong><small><?= e($desc) ?></small></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?= field_error($errors, 'payment_method') ?>

                    <div class="form-grid shipping-fields" id="shippingFields">
                        <label class="span-2">Dirección
                            <input type="text" name="address" value="<?= e($data['address'] === 'Recogida en tienda' ? '' : $data['address']) ?>" autocomplete="street-address">
                            <?= field_error($errors, 'address') ?>
                        </label>
                        <label>Ciudad
                            <input type="text" name="city" value="<?= e($data['city'] === '-' ? '' : $data['city']) ?>" autocomplete="address-level2">
                            <?= field_error($errors, 'city') ?>
                        </label>
                        <label>Código postal
                            <input type="text" name="postal_code" value="<?= e($data['postal_code'] === '00000' ? '' : $data['postal_code']) ?>" inputmode="numeric" maxlength="5" autocomplete="postal-code">
                            <?= field_error($errors, 'postal_code') ?>
                        </label>
                        <label class="span-2">Provincia
                            <input type="text" name="province" value="<?= e($data['province'] === '-' ? '' : $data['province']) ?>" autocomplete="address-level1">
                            <?= field_error($errors, 'province') ?>
                        </label>
                    </div>

                    <label>Notas del pedido (opcional)
                        <textarea name="notes" rows="3" placeholder="Horario de entrega, altura para el ajuste de la bici…"><?= e($data['notes']) ?></textarea>
                    </label>
                </fieldset>

                <label class="check">
                    <input type="checkbox" name="accept" value="1" <?= !empty($_POST['accept']) ? 'checked' : '' ?>>
                    He leído y acepto las condiciones de compra y la política de privacidad.
                </label>
                <?= field_error($errors, 'accept') ?>
            </div>

            <aside class="summary">
                <h3>Tu pedido</h3>
                <ul class="summary-items">
                    <?php foreach ($items as $it): ?>
                        <li>
                            <img src="<?= product_image($it['image']) ?>" alt="">
                            <span><?= e($it['name']) ?><?php if ($it['variant']): ?> <small>(<?= e($it['variant']) ?>)</small><?php endif; ?> <small>× <?= $it['qty'] ?></small></span>
                            <strong><?= money($it['line_total']) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <dl>
                    <div><dt>Subtotal</dt><dd><?= money($totals['subtotal']) ?></dd></div>
                    <div><dt>Envío</dt><dd id="shippingCost" data-cost="<?= e((string)cart_totals($items)['shipping']) ?>"><?= $totals['shipping'] ? money($totals['shipping']) : 'Gratis' ?></dd></div>
                    <div class="summary-total"><dt>Total</dt><dd id="orderTotal" data-subtotal="<?= e((string)$totals['subtotal']) ?>"><?= money($totals['total']) ?></dd></div>
                </dl>
                <p class="tax-note">IVA incluido</p>
                <button class="btn btn-accent btn-lg btn-block" type="submit">Confirmar pedido</button>
            </aside>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
