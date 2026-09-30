-- Esquema de la tienda de bicicletas (MySQL 8 / MariaDB 10.4+)
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS admin_users;
DROP TABLE IF EXISTS newsletter;
DROP TABLE IF EXISTS contact_messages;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type        ENUM('bike','accessory') NOT NULL,
    name        VARCHAR(80)  NOT NULL,
    slug        VARCHAR(80)  NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    sort_order  SMALLINT     NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id   INT UNSIGNED NOT NULL,
    name          VARCHAR(120) NOT NULL,
    slug          VARCHAR(140) NOT NULL UNIQUE,
    short_desc    VARCHAR(255) NULL,
    description   TEXT NULL,
    specs         JSON NULL,                  -- {"Cuadro":"Carbono", ...}
    price         DECIMAL(10,2) NOT NULL,
    compare_price DECIMAL(10,2) NULL,         -- precio anterior (ofertas)
    stock         INT NOT NULL DEFAULT 0,     -- solo si no tiene variantes
    image         VARCHAR(255) NULL,
    featured      TINYINT(1) NOT NULL DEFAULT 0,
    active        TINYINT(1) NOT NULL DEFAULT 1,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_products_active (active, featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tallas / colores con stock propio
CREATE TABLE product_variants (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    label      VARCHAR(60) NOT NULL,
    stock      INT NOT NULL DEFAULT 0,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference      VARCHAR(20) NOT NULL UNIQUE,
    customer_name  VARCHAR(120) NOT NULL,
    email          VARCHAR(160) NOT NULL,
    phone          VARCHAR(30)  NOT NULL,
    address        VARCHAR(255) NOT NULL,
    city           VARCHAR(80)  NOT NULL,
    postal_code    VARCHAR(12)  NOT NULL,
    province       VARCHAR(80)  NOT NULL,
    notes          TEXT NULL,
    payment_method ENUM('card','transfer','cod','store') NOT NULL,
    stripe_session_id     VARCHAR(255) NULL,
    stripe_payment_intent VARCHAR(255) NULL,
    subtotal       DECIMAL(10,2) NOT NULL,
    shipping       DECIMAL(10,2) NOT NULL,
    total          DECIMAL(10,2) NOT NULL,
    status         ENUM('pending','paid','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
    tracking_number VARCHAR(80) NULL,
    paid_at        DATETIME NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_orders_stripe (stripe_session_id),
    INDEX idx_orders_status (status, payment_method, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id      INT UNSIGNED NOT NULL,
    product_id    INT UNSIGNED NULL,
    variant_id    INT UNSIGNED NULL,
    product_name  VARCHAR(120) NOT NULL,
    variant_label VARCHAR(60) NULL,
    unit_price    DECIMAL(10,2) NOT NULL,
    quantity      INT NOT NULL,
    CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    CONSTRAINT fk_items_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(160) NOT NULL UNIQUE,
    name          VARCHAR(80)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE newsletter (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(160) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(160) NOT NULL,
    subject    VARCHAR(160) NULL,
    message    TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
