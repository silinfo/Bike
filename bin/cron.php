<?php
// Tareas periódicas. Prográmalo cada 15 minutos, por ejemplo (crontab -e):
//   */15 * * * * php /ruta/a/la/tienda/bin/cron.php >> /ruta/a/la/tienda/storage/logs/cron.log 2>&1
//
// - Revisa pedidos con tarjeta que se quedaron pendientes (por si algún webhook
//   de Stripe no llegó): los marca como pagados o los cancela liberando el stock.
if (PHP_SAPI !== 'cli') exit('Solo desde la línea de comandos.');
require __DIR__ . '/../includes/bootstrap.php';

$n = stripe_cleanup_stale_orders(100);
echo date('Y-m-d H:i:s') . " Pedidos con tarjeta revisados: {$n}\n";
