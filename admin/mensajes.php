<?php
require __DIR__ . '/_bootstrap.php';

if (($_GET['export'] ?? '') === 'newsletter') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="newsletter.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['email', 'fecha']);
    foreach (db_all('SELECT email, created_at FROM newsletter ORDER BY created_at') as $r) fputcsv($out, $r);
    exit;
}

$messages = db_all('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 100');
$subscribers = (int)db_one('SELECT COUNT(*) AS n FROM newsletter')['n'];

$section = 'mensajes';
$pageTitle = 'Mensajes';
require __DIR__ . '/_header.php';
?>
<div class="page-head">
    <h1>Mensajes</h1>
    <a class="btn" href="?export=newsletter">Exportar newsletter (<?= $subscribers ?>) CSV</a>
</div>

<?php if (!$messages): ?>
    <div class="card"><p class="muted">No hay mensajes de contacto.</p></div>
<?php endif; ?>
<?php foreach ($messages as $m): ?>
    <article class="card message">
        <div class="card-head">
            <strong><?= e($m['name']) ?> · <a href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . ($m['subject'] ?? 'Tu consulta')) ?>"><?= e($m['email']) ?></a></strong>
            <small class="muted"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></small>
        </div>
        <?php if ($m['subject']): ?><h3><?= e($m['subject']) ?></h3><?php endif; ?>
        <p><?= nl2br(e($m['message'])) ?></p>
    </article>
<?php endforeach; ?>
<?php require __DIR__ . '/_footer.php'; ?>
