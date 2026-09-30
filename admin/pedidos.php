<?php
require __DIR__ . '/_bootstrap.php';

$statuses = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];
$status = in_array($_GET['estado'] ?? '', $statuses, true) ? $_GET['estado'] : null;
$q = trim((string)($_GET['q'] ?? ''));

$where = ['1=1']; $params = [];
if ($status) { $where[] = 'o.status = ?'; $params[] = $status; }
if ($q !== '') { $where[] = '(o.reference LIKE ? OR o.customer_name LIKE ? OR o.email LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
$orders = db_all('SELECT o.*, (SELECT SUM(quantity) FROM order_items i WHERE i.order_id = o.id) AS units
                  FROM orders o WHERE ' . implode(' AND ', $where) . ' ORDER BY o.created_at DESC LIMIT 200', $params);

$section = 'pedidos';
$pageTitle = 'Pedidos';
require __DIR__ . '/_header.php';
?>
<div class="page-head"><h1>Pedidos</h1></div>

<form class="toolbar" method="get">
    <select name="estado" onchange="this.form.submit()">
        <option value="">Todos los estados</option>
        <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(order_status_label($s)) ?></option><?php endforeach; ?>
    </select>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Referencia, cliente o email">
    <button class="btn" type="submit">Buscar</button>
</form>

<div class="card">
<?php if ($orders): ?>
<table class="table">
    <thead><tr><th>Referencia</th><th>Fecha</th><th>Cliente</th><th>Uds.</th><th>Pago</th><th>Total</th><th>Estado</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
        <tr>
            <td><a href="<?= url('admin/pedido.php?id=' . $o['id']) ?>"><strong><?= e($o['reference']) ?></strong></a></td>
            <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
            <td><?= e($o['customer_name']) ?><br><small class="muted"><?= e($o['email']) ?></small></td>
            <td><?= (int)$o['units'] ?></td>
            <td><?= e(payment_method_label($o['payment_method'])) ?></td>
            <td><?= money($o['total']) ?></td>
            <td><span class="status status-<?= e($o['status']) ?>"><?= e(order_status_label($o['status'])) ?></span></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?><p class="muted">No hay pedidos.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
