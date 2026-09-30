<?php
// Aviso de versión de PHP antigua. Sintaxis compatible con PHP 5.x/7.x a propósito.
header('HTTP/1.1 500 Internal Server Error');
header('Content-Type: text/html; charset=utf-8');
$host = isset($_SERVER['HTTP_HOST']) ? htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8') : 'tu dominio';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Hay que actualizar PHP</title>
<style>
    body { margin: 0; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; background: #0e0e0e; color: #1c1c1c; line-height: 1.6; padding: 32px 16px; }
    .box { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 32px; }
    h1 { margin: 0 0 12px; font-size: 1.6rem; text-transform: uppercase; }
    .ver { display: inline-block; background: #fdeaea; color: #8f1d1d; padding: 4px 10px; border-radius: 4px; font-weight: 700; }
    ol li { margin-bottom: 8px; }
    code { background: #f5f4f0; padding: 2px 6px; border-radius: 4px; }
</style>
</head>
<body>
<div class="box">
    <h1>Hay que actualizar PHP</h1>
    <p>La tienda necesita <strong>PHP 8.1 o superior</strong> y <?php echo $host; ?> está usando <span class="ver">PHP <?php echo PHP_VERSION; ?></span>.</p>
    <p>En cPanel (HostGator) se cambia en un minuto:</p>
    <ol>
        <li>Entra en cPanel y abre <strong>MultiPHP Manager</strong> (sección «Software»).</li>
        <li>Marca la casilla de <strong><?php echo $host; ?></strong>.</li>
        <li>En el desplegable de la derecha elige <strong>PHP 8.2</strong> (o 8.3) y pulsa <strong>Aplicar</strong>.</li>
        <li>Vuelve a esta página y recárgala.</li>
    </ol>
    <p>Si tu dominio está dentro de otro (dominio adicional o subdominio), cambia la versión de ese dominio concreto.</p>
</div>
</body>
</html>
