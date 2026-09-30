<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Nosotros';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>Nosotros</h1>
        <p>Una tienda de bicis hecha por ciclistas, para ciclistas.</p>
    </div>
</section>

<section class="section">
    <div class="container about-grid">
        <div>
            <span class="eyebrow eyebrow-dark">Nuestra historia</span>
            <h2>Pasión por las dos ruedas desde el primer pedalazo</h2>
            <p>Empezamos en un pequeño taller de barrio y hoy seguimos con la misma filosofía: asesorarte con honestidad, ajustar cada bici a quien la va a usar y acompañarte en cada kilómetro.</p>
            <p>Seleccionamos cuidadosamente cada modelo y cada accesorio que vendemos. Si no lo usaríamos nosotros, no lo vendemos.</p>
        </div>
        <div class="about-values">
            <div><strong>Asesoramiento</strong><span>Te ayudamos a elegir la bici y la talla adecuadas para tu uso.</span></div>
            <div><strong>Ajuste biomecánico</strong><span>Posición óptima para rendir más y evitar lesiones.</span></div>
            <div><strong>Taller</strong><span>Mantenimiento, reparaciones y puesta a punto con recambios de calidad.</span></div>
            <div><strong>Comunidad</strong><span>Salidas en grupo todos los domingos desde la tienda.</span></div>
        </div>
    </div>
</section>

<section class="section section-alt" id="legal">
    <div class="container narrow legal">
        <h2>Envíos y devoluciones</h2>
        <p>Enviamos a toda la península en 24/48 h para accesorios y en 3–5 días laborables para bicicletas (que se envían montadas al 95 % y ajustadas). Dispones de 30 días para devolver cualquier producto sin uso.</p>
        <h2>Aviso legal y privacidad</h2>
        <p>Los datos que nos facilitas se usan únicamente para gestionar tu pedido y, si lo solicitas, enviarte nuestra newsletter. Puedes ejercer tus derechos de acceso, rectificación y supresión escribiendo a <?= e(config('site.email')) ?>.</p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
