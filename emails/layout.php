<?php /** @var string $title @var string $preheader @var string $content */ ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
</head>
<body style="margin:0;padding:0;background:#f1f0ec;font-family:Helvetica,Arial,sans-serif;color:#1c1c1c;">
<span style="display:none;max-height:0;overflow:hidden;opacity:0;"><?= e($preheader ?? '') ?></span>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f0ec;">
<tr><td align="center" style="padding:24px 12px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:6px;overflow:hidden;">
        <tr><td style="background:#0e0e0e;padding:22px 32px;">
            <a href="<?= e(absolute_url()) ?>" style="color:#ffffff;text-decoration:none;font-size:26px;font-weight:800;letter-spacing:4px;"><?= e(config('site.name')) ?><span style="color:#d7263d;">.</span></a>
        </td></tr>
        <tr><td style="padding:32px;">
            <h1 style="margin:0 0 16px;font-size:26px;line-height:1.2;text-transform:uppercase;color:#0e0e0e;"><?= e($title) ?></h1>
            <?= $content ?>
        </td></tr>
        <tr><td style="background:#f5f4f0;padding:22px 32px;font-size:12px;line-height:1.6;color:#6d6d6d;">
            <strong style="color:#1c1c1c;"><?= e(config('site.name')) ?></strong> · <?= e(config('site.address')) ?><br>
            <?= e(config('site.phone')) ?> · <a href="mailto:<?= e(config('site.email')) ?>" style="color:#d7263d;"><?= e(config('site.email')) ?></a><br>
            ¿Dudas con tu pedido? Responde a este email o llámanos.
        </td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
