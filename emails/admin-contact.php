<?php /** @var array $msg */ ?>
<p style="font-size:15px;line-height:1.6;margin:0 0 12px;"><strong><?= e($msg['name']) ?></strong> · <a href="mailto:<?= e($msg['email']) ?>" style="color:#d7263d;"><?= e($msg['email']) ?></a></p>
<?php if ($msg['subject']): ?><p style="font-size:15px;margin:0 0 12px;"><strong>Asunto:</strong> <?= e($msg['subject']) ?></p><?php endif; ?>
<div style="background:#f5f4f0;padding:16px 20px;font-size:15px;line-height:1.6;"><?= nl2br(e($msg['message'])) ?></div>
<p style="font-size:13px;color:#6d6d6d;margin-top:16px;">Responde directamente a este email para contestar.</p>
