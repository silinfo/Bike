# Publicar la tienda en HostGator (cPanel)

Tiempo aproximado: 10–15 minutos. No hace falta SSH ni Composer: el ZIP ya incluye todo.

> **¿Ya tienes una web en tu dominio?** Instala la tienda en una subcarpeta (`public_html/tienda`) para verla en `https://tudominio.com/tienda/` sin tocar lo que ya hay. Cuando estés contento, puedes moverla a la raíz.

---

## 1. Elegir PHP 8.2 o superior (¡imprescindible!)
cPanel → **MultiPHP Manager** → marca tu dominio → elige **PHP 8.2** (o 8.3) → *Aplicar*.
Si la tienda va en un dominio adicional (p. ej. `ciutat.com` dentro de tu cuenta), cambia la versión **de ese dominio**: cada uno tiene la suya. Con PHP 7 verás un aviso con estos pasos.

## 2. Crear la base de datos
cPanel → **Asistente de bases de datos MySQL**:
1. Nombre de la base de datos: por ejemplo `tienda` → quedará como `tuusuario_tienda`.
2. Crea un usuario (p. ej. `tuusuario_bike`) con una contraseña fuerte. **Apúntala.**
3. Marca **TODOS LOS PRIVILEGIOS** → *Siguiente*.

## 3. Subir los archivos
cPanel → **Administrador de archivos** → entra en `public_html`:
1. Crea la carpeta `tienda` (botón *+ Carpeta*) y entra en ella.
2. *Cargar* → sube `tienda-velox.zip`.
3. Clic derecho sobre el ZIP → **Extraer** → en la carpeta actual.
4. Borra el ZIP.

Deberías ver `index.php`, `install.php`, `admin/`, `vendor/`… dentro de `public_html/tienda`.

## 4. Ejecutar el instalador
Abre `https://tudominio.com/tienda/` → te lleva al instalador:
- **Servidor:** `localhost`
- **Base de datos / usuario / contraseña:** los del paso 2 (con el prefijo `tuusuario_`).
- **Tienda:** nombre y email (se usa como remitente y para recibir avisos de pedidos).
- **Administrador:** tu email y contraseña para entrar en `/tienda/admin/`.
- Deja marcado *Cargar productos de ejemplo* para ver la tienda con contenido (los puedes borrar después desde el panel).

## 5. Borrar `install.php` ⚠️
Administrador de archivos → `public_html/tienda/install.php` → **Eliminar**.

## 6. Activar HTTPS
cPanel → **SSL/TLS Status** → *Run AutoSSL* (HostGator lo incluye gratis). Si al entrar por `https://` los enlaces van a `http://`, edita `config.php` y pon `https://` en `app_url`.

Para forzar siempre HTTPS, añade al principio del `.htaccess` de la tienda:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## Configuración opcional (editar `config.php`)
Administrador de archivos → clic derecho en `config.php` → **Edit**.

### Datos de la tienda
Dirección, teléfono, IBAN para transferencias (`bank_iban`, `bank_holder`) y gastos de envío.

### Emails
1. cPanel → **Cuentas de correo electrónico** → crea `pedidos@tudominio.com`.
2. En esa cuenta, *Connect Devices* te muestra el servidor SMTP (normalmente `mail.tudominio.com`, puerto **465**, SSL).
3. En `config.php`:
```php
'mail' => [
    'driver'      => 'smtp',
    'host'        => 'mail.tudominio.com',
    'port'        => 465,
    'encryption'  => 'ssl',
    'username'    => 'pedidos@tudominio.com',
    'password'    => 'la contraseña del correo',
    'from_email'  => 'pedidos@tudominio.com',   // debe ser la misma cuenta
    'from_name'   => 'Tu tienda',
    'admin_email' => 'tu-email@tudominio.com',  // recibe los avisos de pedidos
],
```
Prueba enviando un mensaje desde `/tienda/contacto.php`: debe llegarte al `admin_email`. Si falla, el motivo queda en `storage/logs/app.log`.

### Pago con tarjeta (Stripe)
1. En [dashboard.stripe.com](https://dashboard.stripe.com) (modo prueba) copia la clave `sk_test_...` en `stripe.secret_key`.
2. *Developers → Webhooks → Add endpoint*: `https://tudominio.com/tienda/stripe-webhook.php` con los eventos
   `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed`, `checkout.session.expired`.
3. Copia el *Signing secret* (`whsec_...`) en `stripe.webhook_secret`.
4. Prueba una compra con la tarjeta `4242 4242 4242 4242` (fecha futura, cualquier CVC).
5. Cuando todo vaya bien, repite con las claves reales (`sk_live_...`) y un webhook en modo *live*.

### Tarea programada (cron)
cPanel → **Trabajos de Cron** → *Configuración común*: «Cada 15 minutos» (`*/15 * * * *`) y el comando:
```
/usr/local/bin/php /home/TUUSUARIO/public_html/tienda/bin/cron.php >/dev/null 2>&1
```
(El instalador te muestra la ruta exacta al terminar.) Sirve para liberar el stock de pagos con tarjeta abandonados si algún aviso de Stripe no llega.

---

## Problemas frecuentes

| Síntoma | Solución |
|---|---|
| `Parse error: syntax error, unexpected '\|'` o aviso «Hay que actualizar PHP» | El dominio usa PHP 7. Cámbialo a 8.2 en **MultiPHP Manager** (paso 1). |
| Error 500 / página en blanco | En `config.php` pon `'debug' => true`, recarga para ver el error y vuelve a ponerlo en `false`. También en cPanel → *Errors*. |
| «Faltan las dependencias» | No se subió la carpeta `vendor/`: vuelve a extraer el ZIP completo. |
| La página sale sin estilos | Revisa `base_url` en `config.php` (`/tienda` si está en esa subcarpeta, `''` si está en la raíz). |
| El instalador marca permisos en rojo | Administrador de archivos → carpetas a **755** y archivos a **644**. |
| No llegan los emails | Revisa `storage/logs/app.log`. Usa la misma cuenta en `username` y `from_email`. Mira la carpeta de spam. |
| No se pueden subir fotos grandes | cPanel → *MultiPHP INI Editor* → sube `upload_max_filesize` y `post_max_size` a 8M. |

## Mover la tienda a la raíz del dominio
Mueve todo el contenido de `public_html/tienda` a `public_html` y en `config.php` pon `'base_url' => ''` y `'app_url' => 'https://tudominio.com'`. Actualiza la URL del webhook de Stripe y el comando del cron.

## Actualizar la tienda más adelante
Genera un ZIP nuevo con `bin/empaquetar.sh` (o pídemelo), súbelo y extráelo encima **sin borrar** `config.php`, `uploads/` ni `storage/`. Si hay migraciones nuevas en `database/migrations/`, impórtalas desde cPanel → **phpMyAdmin** → *Importar*.
