<?php
require __DIR__ . '/includes/bootstrap.php';

$data = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($data as $k => $_) $data[$k] = trim((string)($_POST[$k] ?? ''));
    // Campo trampa anti-spam: los humanos no lo ven
    if (!empty($_POST['website'])) redirect('contacto.php');

    if (mb_strlen($data['name']) < 2) $errors['name'] = 'Indica tu nombre.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email no válido.';
    if (mb_strlen($data['message']) < 10) $errors['message'] = 'El mensaje es demasiado corto.';

    if (!$errors) {
        db_exec('INSERT INTO contact_messages (name, email, subject, message) VALUES (?,?,?,?)',
            [$data['name'], $data['email'], $data['subject'] ?: null, $data['message']]);
        mail_admin_contact($data);
        flash('success', '¡Gracias! Te responderemos en menos de 24 horas.');
        redirect('contacto.php');
    }
}

$pageTitle = 'Contacto';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>Contacto</h1>
        <p>¿Dudas con la talla, un pedido o quieres venir a probar una bici? Escríbenos.</p>
    </div>
</section>

<section class="section">
    <div class="container contact-grid">
        <form method="post" class="contact-form" novalidate>
            <?= csrf_field() ?>
            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
            <div class="form-grid">
                <label>Nombre
                    <input type="text" name="name" value="<?= e($data['name']) ?>" required>
                    <?php if (isset($errors['name'])): ?><small class="field-error"><?= e($errors['name']) ?></small><?php endif; ?>
                </label>
                <label>Email
                    <input type="email" name="email" value="<?= e($data['email']) ?>" required>
                    <?php if (isset($errors['email'])): ?><small class="field-error"><?= e($errors['email']) ?></small><?php endif; ?>
                </label>
                <label class="span-2">Asunto
                    <input type="text" name="subject" value="<?= e($data['subject']) ?>">
                </label>
                <label class="span-2">Mensaje
                    <textarea name="message" rows="6" required><?= e($data['message']) ?></textarea>
                    <?php if (isset($errors['message'])): ?><small class="field-error"><?= e($errors['message']) ?></small><?php endif; ?>
                </label>
            </div>
            <button class="btn btn-accent btn-lg" type="submit">Enviar mensaje</button>
        </form>

        <div class="contact-info">
            <h3>Visítanos</h3>
            <p><?= e(config('site.address')) ?></p>
            <h3>Horario</h3>
            <p>Lunes a viernes: 10:00 – 20:00<br>Sábados: 10:00 – 14:00</p>
            <h3>Teléfono y email</h3>
            <p><a href="tel:<?= e(preg_replace('/\s+/', '', config('site.phone'))) ?>"><?= e(config('site.phone')) ?></a><br>
               <a href="mailto:<?= e(config('site.email')) ?>"><?= e(config('site.email')) ?></a></p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
