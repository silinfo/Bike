<?php
declare(strict_types=1);

/*
 * Carrito en sesión: $_SESSION['cart']['<productId>:<variantId|0>'] = cantidad.
 * Los precios y el stock siempre se leen de la base de datos.
 */

function cart_key(int $productId, ?int $variantId): string
{
    return $productId . ':' . ($variantId ?: 0);
}

function cart_raw(): array
{
    return $_SESSION['cart'] ?? [];
}

/**
 * Añade al carrito. Devuelve un mensaje de error o null si todo fue bien.
 */
function cart_add(int $productId, ?int $variantId, int $qty): ?string
{
    $qty = max(1, min(99, $qty));
    $p = db_one('SELECT id, name, stock FROM products WHERE id = ? AND active = 1', [$productId]);
    if (!$p) return 'Producto no disponible.';

    $hasVariants = (bool)db_one('SELECT 1 FROM product_variants WHERE product_id = ? LIMIT 1', [$productId]);
    if ($hasVariants) {
        if (!$variantId) return 'Selecciona una talla.';
        $v = db_one('SELECT stock FROM product_variants WHERE id = ? AND product_id = ?', [$variantId, $productId]);
        if (!$v) return 'Talla no válida.';
        $stock = (int)$v['stock'];
    } else {
        $variantId = null;
        $stock = (int)$p['stock'];
    }

    $key = cart_key($productId, $variantId);
    $current = $_SESSION['cart'][$key] ?? 0;
    if ($current + $qty > $stock) {
        return $stock > $current
            ? "Solo quedan {$stock} unidades disponibles."
            : 'No hay más stock disponible de este producto.';
    }
    $_SESSION['cart'][$key] = $current + $qty;
    return null;
}

function cart_update(string $key, int $qty): void
{
    if (!isset($_SESSION['cart'][$key])) return;
    if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
    } else {
        $_SESSION['cart'][$key] = min(99, $qty);
    }
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

function cart_count(): int
{
    return array_sum(cart_raw());
}

/**
 * Líneas del carrito con datos actuales del producto. Ajusta cantidades al stock
 * y elimina productos que ya no existen.
 */
function cart_items(): array
{
    $items = [];
    foreach (cart_raw() as $key => $qty) {
        [$pid, $vid] = array_map('intval', explode(':', $key));
        $p = db_one('SELECT id, name, slug, price, stock, image FROM products WHERE id = ? AND active = 1', [$pid]);
        if (!$p) { unset($_SESSION['cart'][$key]); continue; }

        $label = null;
        $stock = (int)$p['stock'];
        if ($vid) {
            $v = db_one('SELECT label, stock FROM product_variants WHERE id = ? AND product_id = ?', [$vid, $pid]);
            if (!$v) { unset($_SESSION['cart'][$key]); continue; }
            $label = $v['label'];
            $stock = (int)$v['stock'];
        }
        if ($stock <= 0) { unset($_SESSION['cart'][$key]); continue; }
        if ($qty > $stock) { $qty = $stock; $_SESSION['cart'][$key] = $qty; }

        $items[] = [
            'key'        => $key,
            'product_id' => $pid,
            'variant_id' => $vid ?: null,
            'name'       => $p['name'],
            'slug'       => $p['slug'],
            'image'      => $p['image'],
            'variant'    => $label,
            'price'      => (float)$p['price'],
            'qty'        => $qty,
            'stock'      => $stock,
            'line_total' => (float)$p['price'] * $qty,
        ];
    }
    return $items;
}

function cart_totals(array $items, bool $storePickup = false): array
{
    $subtotal = array_sum(array_column($items, 'line_total'));
    $shipping = 0.0;
    if ($subtotal > 0 && !$storePickup && $subtotal < (float)config('shop.free_shipping_from')) {
        $shipping = (float)config('shop.shipping_cost');
    }
    return [
        'subtotal' => round($subtotal, 2),
        'shipping' => round($shipping, 2),
        'total'    => round($subtotal + $shipping, 2),
    ];
}
