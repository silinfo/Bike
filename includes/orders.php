<?php
declare(strict_types=1);

/*
 * Lógica de pedidos: cambios de estado, stock y emails asociados.
 * Todos los cambios de estado deben pasar por aquí para que el stock
 * y las notificaciones sean coherentes (admin, webhook de Stripe, cron).
 */

function order_find(int $id): ?array
{
    return db_one('SELECT * FROM orders WHERE id = ?', [$id]);
}

function order_find_by_reference(string $ref): ?array
{
    return db_one('SELECT * FROM orders WHERE reference = ?', [$ref]);
}

function order_items(int $orderId): array
{
    return db_all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]);
}

/**
 * Suma ($delta = 1) o resta ($delta = -1) al stock las unidades del pedido.
 */
function order_adjust_stock(int $orderId, int $delta): void
{
    foreach (order_items($orderId) as $it) {
        $qty = $delta * (int)$it['quantity'];
        if ($it['variant_id']) {
            db_exec('UPDATE product_variants SET stock = GREATEST(0, stock + ?) WHERE id = ?', [$qty, $it['variant_id']]);
        } elseif ($it['product_id']) {
            db_exec('UPDATE products SET stock = GREATEST(0, stock + ?) WHERE id = ?', [$qty, $it['product_id']]);
        }
    }
}

/**
 * Cambia el estado de un pedido de forma segura frente a concurrencia:
 * solo se aplica si el pedido sigue en el estado $from esperado.
 * Devuelve true si este proceso fue quien hizo el cambio.
 *
 * $notify = false evita el email al cliente (p. ej. pago con tarjeta abandonado).
 */
function order_transition(int $orderId, string $to, ?string $from = null, bool $notify = true, array $extra = []): bool
{
    $order = order_find($orderId);
    if (!$order || $order['status'] === $to) return false;
    $from ??= $order['status'];

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $sets = ['status = ?'];
        $params = [$to];
        // "Completado" implica cobrado (p. ej. contra reembolso tras la entrega)
        if (in_array($to, ['paid', 'completed'], true)) {
            $sets[] = 'paid_at = COALESCE(paid_at, NOW())';
        }
        foreach ($extra as $col => $val) {
            if (!in_array($col, ['stripe_payment_intent', 'tracking_number'], true)) continue;
            $sets[] = "$col = ?";
            $params[] = $val;
        }
        $params[] = $orderId;
        $params[] = $from;
        $changed = db_exec('UPDATE orders SET ' . implode(', ', $sets) . ' WHERE id = ? AND status = ?', $params) === 1;

        if ($changed) {
            // Cancelar devuelve el stock; reactivar un cancelado lo vuelve a descontar
            if ($to === 'cancelled') order_adjust_stock($orderId, 1);
            elseif ($from === 'cancelled') order_adjust_stock($orderId, -1);
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }

    if ($changed) {
        app_log("Pedido {$order['reference']}: {$from} → {$to}");
        if ($notify) order_notify($orderId, $to, $from);
    }
    return $changed;
}

/**
 * Marca como pagado un pedido con tarjeta. Idempotente: el webhook y la
 * página de éxito pueden llamarlo a la vez y solo uno enviará los emails.
 */
function order_mark_paid(int $orderId, ?string $paymentIntent = null): bool
{
    return order_transition($orderId, 'paid', 'pending', true, ['stripe_payment_intent' => $paymentIntent]);
}

/**
 * Emails que se envían tras cada cambio de estado.
 */
function order_notify(int $orderId, string $to, ?string $from = null): void
{
    $order = order_find($orderId);
    if (!$order) return;

    switch ($to) {
        case 'pending': // pedido recién creado (no tarjeta)
            mail_order_received($order);
            mail_admin_new_order($order);
            break;
        case 'paid':
            mail_order_paid($order);
            // Con tarjeta el aviso a la tienda se envía al confirmarse el pago
            if ($order['payment_method'] === 'card' && $from === 'pending') mail_admin_new_order($order);
            break;
        case 'shipped':
            mail_order_shipped($order);
            break;
        case 'cancelled':
            mail_order_cancelled($order);
            break;
    }
}

/**
 * Crea el pedido a partir del carrito y descuenta el stock.
 * Lanza RuntimeException si algún producto se ha quedado sin stock.
 */
function order_create_from_cart(array $data, array $items, array $totals): array
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        foreach ($items as $it) {
            $affected = $it['variant_id']
                ? db_exec('UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?', [$it['qty'], $it['variant_id'], $it['qty']])
                : db_exec('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?', [$it['qty'], $it['product_id'], $it['qty']]);
            if ($affected !== 1) {
                throw new RuntimeException('Sin stock suficiente de ' . $it['name'] . ($it['variant'] ? ' (' . $it['variant'] . ')' : '') . '.');
            }
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
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
    return order_find($orderId);
}

/**
 * Vuelve a meter en el carrito los productos de un pedido (pago cancelado).
 */
function order_restore_cart(int $orderId): void
{
    foreach (order_items($orderId) as $it) {
        if ($it['product_id']) cart_add((int)$it['product_id'], $it['variant_id'] ? (int)$it['variant_id'] : null, (int)$it['quantity']);
    }
}
