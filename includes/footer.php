</main>

<section class="perks">
    <div class="container perks-grid">
        <div class="perk">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
            <div><strong>Envío gratis</strong><span>Desde <?= money(config('shop.free_shipping_from')) ?></span></div>
        </div>
        <div class="perk">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
            <div><strong>2 años de garantía</strong><span>En todas las bicicletas</span></div>
        </div>
        <div class="perk">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.5-2.5 2.5-2.5Z"/></svg>
            <div><strong>Taller propio</strong><span>Montaje y ajuste incluidos</span></div>
        </div>
        <div class="perk">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
            <div><strong>30 días de devolución</strong><span>Sin preguntas</span></div>
        </div>
    </div>
</section>

<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a href="<?= url() ?>" class="logo logo-light"><?= e(config('site.name')) ?></a>
            <p><?= e(config('site.tagline')) ?>. Diseñamos, montamos y ajustamos cada bicicleta en nuestro taller.</p>
            <form class="newsletter" action="<?= url('newsletter.php') ?>" method="post">
                <?= csrf_field() ?>
                <label for="nl-email">Suscríbete a nuestra newsletter</label>
                <div class="newsletter-row">
                    <input id="nl-email" type="email" name="email" placeholder="Tu email" required>
                    <button type="submit" class="btn btn-accent">Enviar</button>
                </div>
            </form>
        </div>
        <div>
            <h4>Bicicletas</h4>
            <ul>
                <?php foreach (categories('bike') as $c): ?>
                    <li><a href="<?= url('tienda.php?cat=' . urlencode($c['slug'])) ?>"><?= e($c['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h4>Accesorios</h4>
            <ul>
                <?php foreach (categories('accessory') as $c): ?>
                    <li><a href="<?= url('tienda.php?cat=' . urlencode($c['slug'])) ?>"><?= e($c['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h4>Contacto</h4>
            <ul class="footer-contact">
                <li><?= e(config('site.address')) ?></li>
                <li><a href="tel:<?= e(preg_replace('/\s+/', '', config('site.phone'))) ?>"><?= e(config('site.phone')) ?></a></li>
                <li><a href="mailto:<?= e(config('site.email')) ?>"><?= e(config('site.email')) ?></a></li>
                <li>L–V 10:00–20:00 · S 10:00–14:00</li>
            </ul>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© <?= date('Y') ?> <?= e(config('site.name')) ?>. Todos los derechos reservados.</span>
        <span><a href="<?= url('nosotros.php') ?>">Aviso legal</a> · <a href="<?= url('nosotros.php') ?>">Privacidad</a> · <a href="<?= url('nosotros.php') ?>">Envíos y devoluciones</a></span>
    </div>
</footer>

<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
