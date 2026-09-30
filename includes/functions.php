<?php
declare(strict_types=1);

function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) return $default;
        $value = $value[$part];
    }
    return $value;
}

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return config('site.base_url', '') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url($path);
}

function money(float|string $amount): string
{
    return number_format((float)$amount, 2, ',', '.') . ' ' . config('site.currency', '€');
}

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

/**
 * Vuelve a la página anterior solo si es de este mismo sitio.
 */
function redirect_back(string $fallback = ''): never
{
    $back = $_SERVER['HTTP_REFERER'] ?? '';
    $sameHost = $back !== '' && parse_url($back, PHP_URL_HOST) === parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    redirect($sameHost ? $back : $fallback);
}

function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $text));
    return trim($text, '-') ?: 'producto';
}

function product_image(?string $image): string
{
    return asset($image ?: 'assets/img/products/placeholder.svg');
}

// --- Mensajes flash ---
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// --- CSRF ---
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('La sesión ha caducado. Vuelve atrás y recarga la página.');
    }
}

// --- Catálogo ---
function categories(?string $type = null): array
{
    static $cache = null;
    $cache ??= db_all('SELECT * FROM categories ORDER BY sort_order, name');
    return $type ? array_values(array_filter($cache, fn($c) => $c['type'] === $type)) : $cache;
}

/**
 * Stock total disponible de un producto (suma de variantes si las tiene).
 */
function product_stock(array $p): int
{
    return isset($p['variant_stock']) && $p['variant_stock'] !== null ? (int)$p['variant_stock'] : (int)$p['stock'];
}

const PRODUCT_SELECT = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.type,
        (SELECT SUM(v.stock) FROM product_variants v WHERE v.product_id = p.id) AS variant_stock
    FROM products p JOIN categories c ON c.id = p.category_id';

function order_status_label(string $status): string
{
    return [
        'pending'   => 'Pendiente',
        'paid'      => 'Pagado',
        'shipped'   => 'Enviado',
        'completed' => 'Completado',
        'cancelled' => 'Cancelado',
    ][$status] ?? $status;
}

function payment_method_label(string $m): string
{
    return [
        'transfer' => 'Transferencia bancaria',
        'cod'      => 'Contra reembolso',
        'store'    => 'Pago y recogida en tienda',
    ][$m] ?? $m;
}
