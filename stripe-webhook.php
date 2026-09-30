<?php
/*
 * Webhook de Stripe. Configúralo en https://dashboard.stripe.com/webhooks con la URL
 * https://tudominio/stripe-webhook.php y los eventos:
 *   checkout.session.completed
 *   checkout.session.async_payment_succeeded
 *   checkout.session.async_payment_failed
 *   checkout.session.expired
 * Copia el "Signing secret" (whsec_...) en config.php → stripe.webhook_secret
 */
$noSession = true;
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !stripe_enabled() || !config('stripe.webhook_secret')) {
    http_response_code(400);
    exit('{"error":"not configured"}');
}

$payload = file_get_contents('php://input');
try {
    $event = \Stripe\Webhook::constructEvent($payload, $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '', config('stripe.webhook_secret'));
} catch (\UnexpectedValueException | \Stripe\Exception\SignatureVerificationException $ex) {
    app_log('Stripe webhook: firma o contenido no válidos', ['error' => $ex->getMessage()]);
    http_response_code(400);
    exit('{"error":"invalid signature"}');
}

try {
    switch ($event->type) {
        case 'checkout.session.completed':
        case 'checkout.session.async_payment_succeeded':
        case 'checkout.session.expired':
            stripe_apply_session($event->data->object);
            break;
        case 'checkout.session.async_payment_failed':
            $session = $event->data->object;
            $order = order_find((int)($session->metadata['order_id'] ?? 0));
            if ($order && $order['stripe_session_id'] === $session->id) {
                order_transition((int)$order['id'], 'cancelled', 'pending');
            }
            break;
        default:
            // Otros eventos: se ignoran
    }
} catch (Throwable $ex) {
    // Respondemos 500 para que Stripe reintente más tarde
    app_log('Stripe webhook: error procesando evento', ['type' => $event->type, 'error' => $ex->getMessage()]);
    http_response_code(500);
    exit('{"error":"processing failed"}');
}

echo '{"received":true}';
