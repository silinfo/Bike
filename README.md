# VELOX · Tienda de bicicletas

Web + tienda online para vender bicicletas y accesorios. PHP 8, MySQL/MariaDB (PDO) y JavaScript sin dependencias.

## Qué incluye

**Web pública**
- Portada: hero, categorías de bicis, destacadas, banner del taller, accesorios, testimonios.
- Tienda con filtros (tipo, categoría, precio máximo, solo con stock), buscador y orden.
- Ficha de producto: tallas con stock propio, guía de tallas, especificaciones, relacionados.
- Carrito en sesión (añadir por AJAX), barra de envío gratis.
- Checkout: **tarjeta con Stripe** (también Apple Pay / Google Pay), transferencia, contra reembolso o recogida en tienda. El stock se descuenta en una transacción.
- **Emails automáticos**: pedido recibido, pago confirmado, pedido enviado (con nº de seguimiento), cancelación, y avisos a la tienda de nuevos pedidos y mensajes de contacto.
- Nosotros, contacto (con anti-spam) y newsletter.
- Diseño responsive con menú móvil.

**Panel `/admin`**
- Resumen: ventas, pedidos pendientes, stock bajo.
- Productos: crear/editar, tallas, subida de imagen, oferta (precio anterior), destacado, ocultar.
- Pedidos: filtro por estado, detalle, cambio de estado con aviso por email al cliente, nº de seguimiento y enlace al pago en Stripe (cancelar devuelve el stock).
- Mensajes de contacto y exportación CSV de la newsletter.

**Seguridad:** consultas preparadas, CSRF en todos los formularios, `password_hash`, escape de salida, validación de imágenes subidas y bloqueo de PHP en `uploads/`.

## Instalación

```bash
composer install                          # Stripe SDK y PHPMailer
cp config.example.php config.php          # edita datos de BD, tienda, Stripe y email
mysql -u root -e "CREATE DATABASE bike_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root bike_shop < database/schema.sql
mysql -u root bike_shop < database/seed.sql   # datos de ejemplo (opcional)
php database/create_admin.php tu@email.com "contraseña-segura" "Tu nombre"
php -S localhost:8000                     # o súbelo a tu Apache/Nginx
```

**¿Ya tenías la tienda instalada?** Aplica la migración: `mysql bike_shop < database/migrations/001_stripe_y_emails.sql`

Si la web vive en una subcarpeta, pon `'base_url' => '/subcarpeta'` en `config.php`.
En Nginx, bloquea el acceso a `/includes`, `/database`, `/emails`, `/storage`, `/bin`, `/vendor`, `config.php` y la ejecución de PHP en `/uploads` (en Apache ya lo hacen los `.htaccess`).

## Pago con tarjeta (Stripe)

Se usa **Stripe Checkout**: el cliente paga en la página segura de Stripe y los datos de la tarjeta nunca pasan por tu servidor (sin requisitos PCI adicionales).

1. Crea una cuenta en [stripe.com](https://stripe.com) y copia la **clave secreta** (`sk_test_...` para pruebas) en `config.php → stripe.secret_key`. El método «Tarjeta» aparece en el checkout solo si hay clave.
2. En *Developers → Webhooks* añade el endpoint `https://tudominio/stripe-webhook.php` con los eventos
   `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed` y `checkout.session.expired`.
   Copia el *Signing secret* (`whsec_...`) en `stripe.webhook_secret`.
3. Pon la URL pública en `site.app_url` (p. ej. `https://www.velox.bike`).
4. Programa el cron cada 15 min (red de seguridad por si falla algún webhook):
   `*/15 * * * * php /ruta/tienda/bin/cron.php >> /ruta/tienda/storage/logs/cron.log 2>&1`

**Probar en local** con la [Stripe CLI](https://docs.stripe.com/stripe-cli):
`stripe listen --forward-to localhost:8000/stripe-webhook.php` (te da el `whsec_` para `config.php`) y paga con la tarjeta `4242 4242 4242 4242`, cualquier fecha futura y CVC.

**Cómo funciona:** al confirmar se crea el pedido *Pendiente* (reservando stock) y se redirige a Stripe. El webhook lo marca *Pagado* y envía los emails; la página de confirmación también consulta a Stripe por si el webhook se retrasa (sin duplicar emails). Si el cliente vuelve sin pagar, el pedido se cancela, se libera el stock y los productos vuelven a su carrito. Si la sesión de pago caduca (1 h), se cancela igual. Los reembolsos se hacen desde el panel de Stripe (el pedido enlaza al pago).

## Emails

Configura `mail` en `config.php`:
- `driver => 'smtp'` (recomendado): datos SMTP de tu hosting, Google Workspace, Brevo, Mailgun, Amazon SES…
- `driver => 'mail'`: función `mail()` de PHP (más probable que acabe en spam).
- `driver => 'log'` (por defecto): no envía nada; guarda cada email en `storage/mails/` (`.html` para verlo en el navegador y `.eml`). Ideal en desarrollo.

Las plantillas están en `emails/` (HTML con estilos en línea, compatibles con Gmail/Outlook). Los errores de envío se registran en `storage/logs/app.log` y nunca bloquean un pedido; con PHP-FPM los emails se envían después de responder al cliente.
Para mejorar la entrega configura SPF, DKIM y DMARC en el dominio del remitente (`mail.from_email`).

## Estructura

```
index.php, tienda.php, producto.php, carrito.php, checkout.php, pedido.php
nosotros.php, contacto.php, newsletter.php
stripe-webhook.php, pago-cancelado.php
includes/   bootstrap, db (PDO), funciones, carrito, pedidos, stripe, mailer, cabecera/pie
admin/      panel de administración
assets/     css, js e imágenes de ejemplo (SVG)
emails/     plantillas de los emails
bin/        cron.php (tareas periódicas)
storage/    logs y emails guardados en modo 'log'
database/   schema.sql, seed.sql, migrations/, create_admin.php, generate_placeholders.php
uploads/    imágenes subidas desde el panel
```

## Siguientes pasos recomendados
- Reembolsos desde el propio panel (API de Stripe) y facturas en PDF.
- Varias fotos por producto (galería) y SEO con URLs amigables (`/bicicletas/gravel/nomad-gx`).
