# VELOX · Tienda de bicicletas

Web + tienda online para vender bicicletas y accesorios. PHP 8, MySQL/MariaDB (PDO) y JavaScript sin dependencias.

## Qué incluye

**Web pública**
- Portada: hero, categorías de bicis, destacadas, banner del taller, accesorios, testimonios.
- Tienda con filtros (tipo, categoría, precio máximo, solo con stock), buscador y orden.
- Ficha de producto: tallas con stock propio, guía de tallas, especificaciones, relacionados.
- Carrito en sesión (añadir por AJAX), barra de envío gratis.
- Checkout: transferencia, contra reembolso o recogida en tienda. El stock se descuenta en una transacción.
- Nosotros, contacto (con anti-spam) y newsletter.
- Diseño responsive con menú móvil.

**Panel `/admin`**
- Resumen: ventas, pedidos pendientes, stock bajo.
- Productos: crear/editar, tallas, subida de imagen, oferta (precio anterior), destacado, ocultar.
- Pedidos: filtro por estado, detalle y cambio de estado (cancelar devuelve el stock).
- Mensajes de contacto y exportación CSV de la newsletter.

**Seguridad:** consultas preparadas, CSRF en todos los formularios, `password_hash`, escape de salida, validación de imágenes subidas y bloqueo de PHP en `uploads/`.

## Instalación

```bash
cp config.example.php config.php          # edita datos de BD y de la tienda
mysql -u root -e "CREATE DATABASE bike_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root bike_shop < database/schema.sql
mysql -u root bike_shop < database/seed.sql   # datos de ejemplo (opcional)
php database/create_admin.php tu@email.com "contraseña-segura" "Tu nombre"
php -S localhost:8000                     # o súbelo a tu Apache/Nginx
```

Si la web vive en una subcarpeta, pon `'base_url' => '/subcarpeta'` en `config.php`.
En Nginx, bloquea el acceso a `/includes`, `/database`, `config.php` y la ejecución de PHP en `/uploads` (en Apache ya lo hacen los `.htaccess`).

## Estructura

```
index.php, tienda.php, producto.php, carrito.php, checkout.php, pedido.php
nosotros.php, contacto.php, newsletter.php
includes/   bootstrap, db (PDO), funciones, carrito, cabecera/pie
admin/      panel de administración
assets/     css, js e imágenes de ejemplo (SVG)
database/   schema.sql, seed.sql, create_admin.php, generate_placeholders.php
uploads/    imágenes subidas desde el panel
```

## Siguientes pasos recomendados
- **Pago con tarjeta:** integrar Stripe Checkout o Redsys en `checkout.php` (hay un comentario donde va la redirección).
- **Emails** de confirmación de pedido (PHPMailer + SMTP).
- Varias fotos por producto (galería) y SEO con URLs amigables (`/bicicletas/gravel/nomad-gx`).
