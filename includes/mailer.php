<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Envía un email HTML (la versión de texto plano se genera automáticamente).
 * Nunca lanza excepciones: un fallo de correo no debe romper un pedido.
 *
 * Con PHP-FPM el envío se aplaza hasta después de responder al navegador
 * (fastcgi_finish_request), así un servidor SMTP lento no retrasa la compra.
 */
function mail_send(string $to, string $subject, string $html, ?string $replyTo = null): bool
{
    static $queue = null;
    if (function_exists('fastcgi_finish_request') && PHP_SAPI !== 'cli') {
        if ($queue === null) {
            $queue = [];
            register_shutdown_function(function () use (&$queue) {
                if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
                fastcgi_finish_request();
                foreach ($queue as $m) mail_deliver(...$m);
            });
        }
        $queue[] = [$to, $subject, $html, $replyTo];
        return true;
    }
    return mail_deliver($to, $subject, $html, $replyTo);
}

function mail_deliver(string $to, string $subject, string $html, ?string $replyTo = null): bool
{
    $driver = config('mail.driver', 'log');
    $mail = new PHPMailer(true);
    try {
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom(config('mail.from_email'), config('mail.from_name', config('site.name')));
        $mail->addAddress($to);
        if ($replyTo) $mail->addReplyTo($replyTo);
        $mail->Subject = $subject;
        $mail->msgHTML($html);

        if ($driver === 'smtp') {
            $mail->isSMTP();
            $mail->Host = config('mail.host');
            $mail->Port = (int)config('mail.port', 587);
            $mail->SMTPAuth = config('mail.username', '') !== '';
            $mail->Username = config('mail.username', '');
            $mail->Password = config('mail.password', '');
            $enc = config('mail.encryption', 'tls');
            $mail->SMTPSecure = $enc === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : ($enc === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : '');
            $mail->SMTPAutoTLS = $enc !== '';
            // Límites cortos: por defecto PHPMailer puede esperar hasta 5 minutos
            $mail->Timeout = 10;
            $mail->getSMTPInstance()->Timelimit = 10;
        } elseif ($driver === 'mail') {
            $mail->isMail();
        } else {
            // 'log': guarda el email en storage/mails para revisarlo sin enviarlo
            $mail->preSend();
            $name = date('Ymd-His') . '-' . substr(slugify($subject), 0, 40) . '-' . bin2hex(random_bytes(2));
            $dir = __DIR__ . '/../storage/mails/';
            file_put_contents($dir . $name . '.eml', $mail->getSentMIMEMessage());
            file_put_contents($dir . $name . '.html', '<!-- Para: ' . htmlspecialchars($to) . ' | Asunto: ' . htmlspecialchars($subject) . " -->\n" . $html);
            app_log("Email guardado (log): {$subject} → {$to}");
            return true;
        }

        $mail->send();
        app_log("Email enviado: {$subject} → {$to}");
        return true;
    } catch (MailException $ex) {
        app_log('Error enviando email', ['to' => $to, 'subject' => $subject, 'error' => $mail->ErrorInfo ?: $ex->getMessage()]);
        return false;
    }
}

/**
 * Renderiza una plantilla de emails/ dentro del layout común.
 */
function mail_render(string $template, array $vars = []): string
{
    $render = function (string $__file, array $__vars): string {
        extract($__vars, EXTR_SKIP);
        ob_start();
        require $__file;
        return (string)ob_get_clean();
    };
    $content = $render(__DIR__ . '/../emails/' . $template . '.php', $vars);
    return $render(__DIR__ . '/../emails/layout.php', $vars + ['content' => $content]);
}

/**
 * Datos comunes que usan las plantillas de pedidos.
 */
function mail_order_vars(array $order, string $title, string $preheader): array
{
    return [
        'order'     => $order,
        'items'     => order_items((int)$order['id']),
        'title'     => $title,
        'preheader' => $preheader,
    ];
}

function mail_order_received(array $order): bool
{
    $vars = mail_order_vars($order, '¡Gracias por tu pedido!', 'Hemos recibido tu pedido ' . $order['reference']);
    return mail_send($order['email'], 'Hemos recibido tu pedido ' . $order['reference'], mail_render('order-received', $vars));
}

function mail_order_paid(array $order): bool
{
    $title = $order['payment_method'] === 'card' ? '¡Pedido confirmado!' : 'Pago recibido';
    $vars = mail_order_vars($order, $title, 'Tu pago del pedido ' . $order['reference'] . ' se ha confirmado');
    return mail_send($order['email'], $title . ' · Pedido ' . $order['reference'], mail_render('order-paid', $vars));
}

function mail_order_shipped(array $order): bool
{
    $vars = mail_order_vars($order, '¡Tu pedido está en camino!', 'Hemos enviado tu pedido ' . $order['reference']);
    $subject = $order['payment_method'] === 'store' ? 'Tu pedido está listo para recoger' : 'Tu pedido ' . $order['reference'] . ' está en camino';
    if ($order['payment_method'] === 'store') $vars['title'] = '¡Tu pedido está listo!';
    return mail_send($order['email'], $subject, mail_render('order-shipped', $vars));
}

function mail_order_cancelled(array $order): bool
{
    $vars = mail_order_vars($order, 'Pedido cancelado', 'Tu pedido ' . $order['reference'] . ' ha sido cancelado');
    return mail_send($order['email'], 'Pedido ' . $order['reference'] . ' cancelado', mail_render('order-cancelled', $vars));
}

function mail_admin_new_order(array $order): bool
{
    $to = config('mail.admin_email');
    if (!$to) return false;
    $vars = mail_order_vars($order, 'Nuevo pedido ' . $order['reference'], money($order['total']) . ' · ' . $order['customer_name']);
    return mail_send($to, '🛒 Nuevo pedido ' . $order['reference'] . ' · ' . money($order['total']), mail_render('admin-new-order', $vars), $order['email']);
}

function mail_admin_contact(array $msg): bool
{
    $to = config('mail.admin_email');
    if (!$to) return false;
    $vars = ['msg' => $msg, 'title' => 'Nuevo mensaje de contacto', 'preheader' => $msg['name'] . ': ' . mb_substr($msg['message'], 0, 80)];
    return mail_send($to, 'Contacto web: ' . ($msg['subject'] ?: $msg['name']), mail_render('admin-contact', $vars), $msg['email']);
}
