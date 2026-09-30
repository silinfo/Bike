<?php
require __DIR__ . '/includes/bootstrap.php';

$featuredBikes = db_all(PRODUCT_SELECT . " WHERE p.active = 1 AND c.type = 'bike' ORDER BY p.featured DESC, p.created_at DESC LIMIT 4");
$accessories   = db_all(PRODUCT_SELECT . " WHERE p.active = 1 AND c.type = 'accessory' ORDER BY p.featured DESC, p.created_at DESC LIMIT 4");
$heroBike      = $featuredBikes[0] ?? null;

// Imagen representativa de cada categoría de bicis
$bikeCats = db_all("SELECT c.*, (SELECT p.image FROM products p WHERE p.category_id = c.id AND p.active = 1 ORDER BY p.featured DESC, p.id LIMIT 1) AS image,
                           (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.active = 1) AS total
                    FROM categories c WHERE c.type = 'bike' ORDER BY c.sort_order");

$bodyClass = 'home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container hero-inner">
        <div class="hero-text">
            <span class="eyebrow">Nueva colección <?= date('Y') ?></span>
            <h1>Rueda más lejos.<br><em>Rueda mejor.</em></h1>
            <p>Bicicletas de carretera, gravel, montaña, urbanas y eléctricas. Montadas y ajustadas a tu medida en nuestro taller.</p>
            <div class="hero-cta">
                <a href="<?= url('tienda.php?tipo=bike') ?>" class="btn btn-accent btn-lg">Ver bicicletas</a>
                <a href="<?= url('tienda.php?tipo=accessory') ?>" class="btn btn-outline-light btn-lg">Accesorios</a>
            </div>
        </div>
        <?php if ($heroBike): ?>
            <a class="hero-media" href="<?= url('producto.php?slug=' . urlencode($heroBike['slug'])) ?>">
                <img src="<?= product_image($heroBike['image']) ?>" alt="<?= e($heroBike['name']) ?>">
                <span class="hero-tag">
                    <small><?= e($heroBike['category_name']) ?></small>
                    <strong><?= e($heroBike['name']) ?></strong>
                    <span>Desde <?= money($heroBike['price']) ?></span>
                </span>
            </a>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>Encuentra tu bici</h2>
            <a href="<?= url('tienda.php?tipo=bike') ?>" class="link-arrow">Ver todas</a>
        </div>
        <div class="category-grid">
            <?php foreach ($bikeCats as $c): ?>
                <a class="category-card" href="<?= url('tienda.php?cat=' . urlencode($c['slug'])) ?>">
                    <img src="<?= product_image($c['image']) ?>" alt="" loading="lazy">
                    <div class="category-card-body">
                        <h3><?= e($c['name']) ?></h3>
                        <span><?= (int)$c['total'] ?> modelos</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <h2>Bicis destacadas</h2>
            <a href="<?= url('tienda.php?tipo=bike') ?>" class="link-arrow">Ir a la tienda</a>
        </div>
        <div class="product-grid">
            <?php foreach ($featuredBikes as $p) include __DIR__ . '/includes/product-card.php'; ?>
        </div>
    </div>
</section>

<section class="banner">
    <div class="container banner-inner">
        <div class="banner-text">
            <span class="eyebrow">Nuestro taller</span>
            <h2>Cada bici, ajustada a ti</h2>
            <p>Medimos, montamos y ajustamos tu bicicleta antes de entregártela. Estudio biomecánico, primera revisión gratuita y mantenimiento con mecánicos certificados.</p>
            <a href="<?= url('nosotros.php') ?>" class="btn btn-light">Conócenos</a>
        </div>
        <div class="banner-stats">
            <div><strong>15+</strong><span>años sobre ruedas</span></div>
            <div><strong>4.800</strong><span>bicis entregadas</span></div>
            <div><strong>4,9★</strong><span>valoración media</span></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>Accesorios</h2>
            <a href="<?= url('tienda.php?tipo=accessory') ?>" class="link-arrow">Ver todos</a>
        </div>
        <div class="chip-row">
            <?php foreach (categories('accessory') as $c): ?>
                <a class="chip" href="<?= url('tienda.php?cat=' . urlencode($c['slug'])) ?>"><?= e($c['name']) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="product-grid">
            <?php foreach ($accessories as $p) include __DIR__ . '/includes/product-card.php'; ?>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container testimonials">
        <h2>Lo que dicen nuestros clientes</h2>
        <div class="testimonial-grid">
            <blockquote>
                <p>“Me ajustaron la Nomad al milímetro. He hecho mi primera ruta de 3 días sin una sola molestia.”</p>
                <cite>Laura M. · Gravel</cite>
            </blockquote>
            <blockquote>
                <p>“Trato cercano, asesoramiento honesto y un taller que responde. Repetiré seguro.”</p>
                <cite>Javier R. · Carretera</cite>
            </blockquote>
            <blockquote>
                <p>“La eléctrica ha cambiado cómo me muevo por la ciudad. Ya no uso el coche para ir al trabajo.”</p>
                <cite>Marta G. · Eléctrica</cite>
            </blockquote>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
