<?php
declare(strict_types=1);

/*
 * Pago con tarjeta mediante Stripe Checkout (página de pago alojada por Stripe).
 * Los datos de la tarjeta nunca pasan por nuestro servidor.
 *
 * Flujo:
 *  1. checkout.php crea el pedido (pendiente, stock reservado) y una Checkout Session.
 *  2. El cliente paga en Stripe y vuelve a pedido.php?ref=...&session_id=...
 *  3. El webhook (stripe-webhook.php) confirma el pago → pedido "Pagado" + emails.
 *     pedido.php también consulta la sesión por si el webhook se retrasa.
 *  4. Si el cliente cancela (pago-cancelado.php) o la sesión caduca (webhook / bin/cron.php)
 *     el pedido se cancela y se repone el stock.
 */

function stripe_enabled(): bool
{
    return (string)config('stripe.secret_key', '') !== '';
}

function stripe_client(): \Stripe\StripeClient
{
    static $client = null;
    if ($client === null) {
        $opts = ['api_key' => config('stripe.secret_key')];
        // Solo para pruebas locales contra un servidor simulado
        if ($base = config('stripe.api_base')) $opts['api_base'] = $base;
        $client = new \Stripe\StripeClient($opts);
    }
    return $client;
}

function stripe_amount(float|string $euros): int
{
    return (int)round((float)$euros * 100);
}

/**
 * Crea la Checkout Session de un pedido y devuelve la URL de pago.
 */
function stripe_create_checkout(array $order): string
{
    $currency = config('shop.currency_code', 'eur');
    $lineItems = [];
    foreach (order_items((int)$order['id']) as $it) {
        $product = ['name' => $it['product_name'] . ($it['variant_label'] ? ' · Talla ' . $it['variant_label'] : '')];
        // Stripe solo admite imágenes públicas JPG/PNG/WEBP
        $img = $it['product_id'] ? (db_one('SELECT image FROM products WHERE id = ?', [$it['product_id']])['image'] ?? null) : null;
        if ($img && preg_match('/\.(jpe?g|png|webp)$/i', $img) && str_starts_with(absolute_url(), 'https://')) {
            $product['images'] = [absolute_url($img)];
        }
        $lineItems[] = [
            'quantity'   => (int)$it['quantity'],
            'price_data' => [
                'currency'     => $currency,
                'unit_amount'  => stripe_amount($it['unit_price']),
                'product_data' => $product,
            ],
        ];
    }

    $ref = $order['reference'];
    $session = stripe_client()->checkout->sessions->create([
        'mode'                => 'payment',
        'locale'              => 'es',
        'customer_email'      => $order['email'],
        'client_reference_id' => $ref,
        'line_items'          => $lineItems,
        'shipping_options'    => [[
            'shipping_rate_data' => [
                'type'         => 'fixed_amount',
                'display_name' => (float)$order['shipping'] > 0 ? 'Envío estándar' : 'Envío gratuito',
                'fixed_amount' => ['amount' => stripe_amount($order['shipping']), 'currency' => $currency],
            ],
        ]],
        'metadata'            => ['order_id' => (string)$order['id'], 'order_reference' => $ref],
        'payment_intent_data' => [
            'description' => 'Pedido ' . $ref,
            'metadata'    => ['order_id' => (string)$order['id'], 'order_reference' => $ref],
        ],
        // Mínimo 30 minutos; al caducar se cancela el pedido y se libera el stock
        'expires_at'          => time() + 3600,
        'success_url'         => absolute_url('pedido.php?ref=' . urlencode($ref)) . '&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'          => absolute_url('pago-cancelado.php?ref=' . urlencode($ref)),
    ], ['idempotency_key' => 'checkout-' . $ref]);

    db_exec('UPDATE orders SET stripe_session_id = ? WHERE id = ?', [$session->id, $order['id']]);
    return $session->url;
}

/**
 * Aplica al pedido el estado de su Checkout Session (pagada / caducada).
 * Se usa desde el webhook, la página de éxito y el cron.
 */
function stripe_apply_session(\Stripe\Checkout\Session $session): void
{
    $orderId = (int)($session->metadata['order_id'] ?? 0);
    $order = $orderId ? order_find($orderId) : null;
    if (!$order || $order['stripe_session_id'] !== $session->id) {
        app_log('Stripe: sesión sin pedido asociado', ['session' => $session->id, 'order_id' => $orderId]);
        return;
    }

    if ($session->payment_status === 'paid' || $session->payment_status === 'no_payment_required') {
        if ((int)$session->amount_total !== stripe_amount($order['total'])) {
            app_log('Stripe: el importe cobrado no coincide con el pedido', ['order' => $order['reference'], 'cobrado' => $session->amount_total, 'esperado' => stripe_amount($order['total'])]);
            return; // se revisa a mano desde el panel de Stripe
        }
        $pi = is_string($session->payment_intent) ? $session->payment_intent : ($session->payment_intent->id ?? null);
        order_mark_paid($orderId, $pi);
    } elseif ($session->status === 'expired') {
        // Pago abandonado: se cancela sin email al cliente
        order_transition($orderId, 'cancelled', 'pending', false);
    }
}

/**
 * Consulta en Stripe la sesión de un pedido pendiente y la aplica.
 */
function stripe_sync_order(array $order): void
{
    if ($order['payment_method'] !== 'card' || $order['status'] !== 'pending' || !$order['stripe_session_id'] || !stripe_enabled()) return;
    try {
        stripe_apply_session(stripe_client()->checkout->sessions->retrieve($order['stripe_session_id']));
    } catch (\Stripe\Exception\ApiErrorException $ex) {
        app_log('Stripe: error al consultar la sesión', ['order' => $order['reference'], 'error' => $ex->getMessage()]);
    }
}

/**
 * Cancela la sesión de pago de un pedido (el cliente volvió sin pagar).
 * Devuelve true si el pedido quedó cancelado; false si en realidad ya estaba pagado.
 */
function stripe_cancel_checkout(array $order): bool
{
    if ($order['status'] !== 'pending') return $order['status'] === 'cancelled';
    try {
        stripe_client()->checkout->sessions->expire($order['stripe_session_id']);
    } catch (\Stripe\Exception\ApiErrorException $ex) {
        // Si no se puede caducar es que ya se completó (o ya caducó): miramos su estado real
        stripe_sync_order($order);
        return order_find((int)$order['id'])['status'] === 'cancelled';
    }
    return order_transition((int)$order['id'], 'cancelled', 'pending', false);
}

/**
 * Revisa pedidos con tarjeta que siguen pendientes tras la caducidad de su
 * sesión (por si algún webhook no llegó). Llamado desde bin/cron.php y el panel.
 */
function stripe_cleanup_stale_orders(int $limit = 20): int
{
    if (!stripe_enabled()) return 0;
    $stale = db_all("SELECT * FROM orders WHERE payment_method = 'card' AND status = 'pending'
                     AND created_at < NOW() - INTERVAL 75 MINUTE ORDER BY created_at LIMIT " . (int)$limit);
    foreach ($stale as $o) {
        if (!$o['stripe_session_id']) {
            // No se llegó a crear la sesión de pago
            order_transition((int)$o['id'], 'cancelled', 'pending', false);
        } else {
            stripe_sync_order($o);
        }
    }
    return count($stale);
}
