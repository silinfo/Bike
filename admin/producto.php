<?php
require __DIR__ . '/_bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$product = $id ? db_one('SELECT * FROM products WHERE id = ?', [$id]) : null;
if ($id && !$product) { flash('error', 'Producto no encontrado.'); redirect('admin/productos.php'); }

$variants = $id ? db_all('SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order, id', [$id]) : [];
$specsToText = function (?string $json): string {
    $out = '';
    foreach (($json ? json_decode($json, true) : []) ?: [] as $k => $v) $out .= "$k: $v\n";
    return rtrim($out);
};

$data = $product ?? [
    'category_id' => '', 'name' => '', 'slug' => '', 'short_desc' => '', 'description' => '',
    'price' => '', 'compare_price' => '', 'stock' => 0, 'image' => null, 'featured' => 0, 'active' => 1, 'specs' => null,
];
$specsText = $specsToText($data['specs']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data['category_id']   = (int)($_POST['category_id'] ?? 0);
    $data['name']          = trim((string)($_POST['name'] ?? ''));
    $data['slug']          = slugify(trim((string)($_POST['slug'] ?? '')) ?: $data['name']);
    $data['short_desc']    = trim((string)($_POST['short_desc'] ?? ''));
    $data['description']   = trim((string)($_POST['description'] ?? ''));
    $data['price']         = str_replace(',', '.', trim((string)($_POST['price'] ?? '')));
    $data['compare_price'] = str_replace(',', '.', trim((string)($_POST['compare_price'] ?? '')));
    $data['stock']         = max(0, (int)($_POST['stock'] ?? 0));
    $data['featured']      = !empty($_POST['featured']) ? 1 : 0;
    $data['active']        = !empty($_POST['active']) ? 1 : 0;
    $specsText             = trim((string)($_POST['specs'] ?? ''));

    // Especificaciones "Clave: valor" por línea → JSON
    $specs = [];
    foreach (preg_split('/\R/', $specsText) as $line) {
        if (str_contains($line, ':')) {
            [$k, $v] = array_map('trim', explode(':', $line, 2));
            if ($k !== '') $specs[$k] = $v;
        }
    }

    // Variantes enviadas desde el formulario
    $postedVariants = [];
    foreach ((array)($_POST['variants'] ?? []) as $i => $v) {
        $label = trim((string)($v['label'] ?? ''));
        if ($label === '') continue;
        $postedVariants[] = ['id' => (int)($v['id'] ?? 0), 'label' => mb_substr($label, 0, 60), 'stock' => max(0, (int)($v['stock'] ?? 0)), 'sort_order' => count($postedVariants) + 1];
    }

    if ($data['name'] === '') $errors['name'] = 'El nombre es obligatorio.';
    if (!db_one('SELECT 1 FROM categories WHERE id = ?', [$data['category_id']])) $errors['category_id'] = 'Elige una categoría.';
    if (!is_numeric($data['price']) || $data['price'] <= 0) $errors['price'] = 'Precio no válido.';
    if ($data['compare_price'] !== '' && (!is_numeric($data['compare_price']) || $data['compare_price'] <= $data['price'])) $errors['compare_price'] = 'Debe ser mayor que el precio actual (o vacío).';
    if (db_one('SELECT 1 FROM products WHERE slug = ? AND id <> ?', [$data['slug'], $id])) $errors['slug'] = 'Ya existe un producto con esa URL.';

    // Imagen
    $newImage = null;
    if (!empty($_FILES['image']['name'])) {
        $f = $_FILES['image'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = $f['error'] === UPLOAD_ERR_OK ? (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) : null;
        if ($f['error'] !== UPLOAD_ERR_OK) $errors['image'] = 'Error al subir la imagen.';
        elseif (!isset($allowed[$mime]) || !getimagesize($f['tmp_name'])) $errors['image'] = 'Formato no permitido (JPG, PNG o WEBP).';
        elseif ($f['size'] > 5 * 1024 * 1024) $errors['image'] = 'La imagen supera los 5 MB.';
        else {
            $newImage = 'uploads/' . $data['slug'] . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
        }
    }

    if (!$errors) {
        if ($newImage) {
            move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../' . $newImage);
            if (!empty($data['image']) && str_starts_with($data['image'], 'uploads/')) @unlink(__DIR__ . '/../' . $data['image']);
            $data['image'] = $newImage;
        }
        $row = [
            $data['category_id'], $data['name'], $data['slug'], $data['short_desc'] ?: null, $data['description'] ?: null,
            $specs ? json_encode($specs, JSON_UNESCAPED_UNICODE) : null, $data['price'],
            $data['compare_price'] === '' ? null : $data['compare_price'], $data['stock'], $data['image'], $data['featured'], $data['active'],
        ];

        $pdo = db();
        $pdo->beginTransaction();
        if ($id) {
            db_exec('UPDATE products SET category_id=?, name=?, slug=?, short_desc=?, description=?, specs=?, price=?, compare_price=?, stock=?, image=?, featured=?, active=? WHERE id=?', [...$row, $id]);
        } else {
            db_exec('INSERT INTO products (category_id, name, slug, short_desc, description, specs, price, compare_price, stock, image, featured, active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)', $row);
            $id = (int)$pdo->lastInsertId();
        }

        // Sincroniza variantes: actualiza, crea y borra
        $keep = [];
        foreach ($postedVariants as $v) {
            if ($v['id'] && db_one('SELECT 1 FROM product_variants WHERE id = ? AND product_id = ?', [$v['id'], $id])) {
                db_exec('UPDATE product_variants SET label=?, stock=?, sort_order=? WHERE id=?', [$v['label'], $v['stock'], $v['sort_order'], $v['id']]);
                $keep[] = $v['id'];
            } else {
                db_exec('INSERT INTO product_variants (product_id, label, stock, sort_order) VALUES (?,?,?,?)', [$id, $v['label'], $v['stock'], $v['sort_order']]);
                $keep[] = (int)$pdo->lastInsertId();
            }
        }
        $placeholders = $keep ? implode(',', array_fill(0, count($keep), '?')) : '0';
        db_exec("DELETE FROM product_variants WHERE product_id = ? AND id NOT IN ($placeholders)", [$id, ...$keep]);
        $pdo->commit();

        flash('success', 'Producto guardado.');
        redirect('admin/producto.php?id=' . $id);
    }
    $variants = $postedVariants;
}

$section = 'productos';
$pageTitle = $product ? 'Editar ' . $product['name'] : 'Nuevo producto';
require __DIR__ . '/_header.php';

$err = fn(string $f) => isset($errors[$f]) ? '<small class="field-error">' . e($errors[$f]) . '</small>' : '';
?>
<div class="page-head">
    <h1><?= e($pageTitle) ?></h1>
    <?php if ($product): ?><a href="<?= url('producto.php?slug=' . urlencode($product['slug'])) ?>" target="_blank">Ver en la tienda ↗</a><?php endif; ?>
</div>

<form method="post" enctype="multipart/form-data" class="product-form">
    <?= csrf_field() ?>
    <div class="grid-main">
        <section class="card">
            <label>Nombre <input type="text" name="name" value="<?= e($data['name']) ?>" required><?= $err('name') ?></label>
            <label>URL (slug) <input type="text" name="slug" value="<?= e($data['slug']) ?>" placeholder="Se genera a partir del nombre"><?= $err('slug') ?></label>
            <label>Descripción corta <input type="text" name="short_desc" value="<?= e($data['short_desc']) ?>" maxlength="255"></label>
            <label>Descripción <textarea name="description" rows="6"><?= e($data['description']) ?></textarea></label>
            <label>Especificaciones <small class="muted">(una por línea: <code>Cuadro: Carbono</code>)</small>
                <textarea name="specs" rows="6" class="mono"><?= e($specsText) ?></textarea></label>
        </section>

        <section class="card">
            <div class="card-head"><h2>Tallas / variantes</h2><button type="button" class="btn btn-sm" id="addVariant">+ Añadir</button></div>
            <p class="muted">Si el producto tiene tallas, el stock se controla por talla. Si no, deja la lista vacía y usa el stock general.</p>
            <div id="variants">
                <?php foreach ($variants as $i => $v): ?>
                    <div class="variant-row">
                        <input type="hidden" name="variants[<?= $i ?>][id]" value="<?= (int)$v['id'] ?>">
                        <input type="text" name="variants[<?= $i ?>][label]" value="<?= e($v['label']) ?>" placeholder="Talla (S, M, L…)">
                        <input type="number" name="variants[<?= $i ?>][stock]" value="<?= (int)$v['stock'] ?>" min="0" placeholder="Stock">
                        <button type="button" class="link danger" data-remove>Quitar</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <template id="variantTpl">
                <div class="variant-row">
                    <input type="hidden" name="variants[__i__][id]" value="0">
                    <input type="text" name="variants[__i__][label]" placeholder="Talla (S, M, L…)">
                    <input type="number" name="variants[__i__][stock]" value="0" min="0" placeholder="Stock">
                    <button type="button" class="link danger" data-remove>Quitar</button>
                </div>
            </template>
        </section>
    </div>

    <div class="grid-aside">
        <section class="card">
            <label>Categoría
                <select name="category_id" required>
                    <option value="">— Elegir —</option>
                    <optgroup label="Bicicletas">
                        <?php foreach (categories('bike') as $c): ?><option value="<?= $c['id'] ?>" <?= $data['category_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Accesorios">
                        <?php foreach (categories('accessory') as $c): ?><option value="<?= $c['id'] ?>" <?= $data['category_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                    </optgroup>
                </select><?= $err('category_id') ?>
            </label>
            <label>Precio (€) <input type="text" name="price" value="<?= e((string)$data['price']) ?>" inputmode="decimal" required><?= $err('price') ?></label>
            <label>Precio anterior (€) <small class="muted">para mostrar oferta</small><input type="text" name="compare_price" value="<?= e((string)$data['compare_price']) ?>" inputmode="decimal"><?= $err('compare_price') ?></label>
            <label>Stock general <small class="muted">(sin tallas)</small><input type="number" name="stock" value="<?= (int)$data['stock'] ?>" min="0"></label>
            <label class="check"><input type="checkbox" name="featured" value="1" <?= $data['featured'] ? 'checked' : '' ?>> Destacado en portada</label>
            <label class="check"><input type="checkbox" name="active" value="1" <?= $data['active'] ? 'checked' : '' ?>> Visible en la tienda</label>
        </section>

        <section class="card">
            <h2>Imagen</h2>
            <img class="preview" id="imgPreview" src="<?= product_image($data['image']) ?>" alt="">
            <label>Subir nueva (JPG, PNG, WEBP · máx. 5 MB)<input type="file" name="image" accept="image/jpeg,image/png,image/webp" id="imgInput"></label>
            <?= $err('image') ?>
        </section>

        <button class="btn btn-accent btn-block" type="submit">Guardar producto</button>
    </div>
</form>

<script>
(() => {
    const box = document.getElementById('variants');
    const tpl = document.getElementById('variantTpl').innerHTML;
    let i = <?= count($variants) ?> + 100;
    document.getElementById('addVariant').addEventListener('click', () => {
        box.insertAdjacentHTML('beforeend', tpl.replaceAll('__i__', i++));
        box.lastElementChild.querySelector('input[type=text]').focus();
    });
    box.addEventListener('click', e => { if (e.target.matches('[data-remove]')) e.target.closest('.variant-row').remove(); });
    document.getElementById('imgInput').addEventListener('change', e => {
        const f = e.target.files[0];
        if (f) document.getElementById('imgPreview').src = URL.createObjectURL(f);
    });
})();
</script>
<?php require __DIR__ . '/_footer.php'; ?>
