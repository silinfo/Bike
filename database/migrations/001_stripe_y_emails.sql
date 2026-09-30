-- Actualiza una instalación existente para el pago con Stripe y los emails.
-- mysql bike_shop < database/migrations/001_stripe_y_emails.sql
ALTER TABLE orders
    MODIFY payment_method ENUM('card','transfer','cod','store') NOT NULL,
    ADD COLUMN stripe_session_id     VARCHAR(255) NULL AFTER payment_method,
    ADD COLUMN stripe_payment_intent VARCHAR(255) NULL AFTER stripe_session_id,
    ADD COLUMN tracking_number       VARCHAR(80)  NULL AFTER status,
    ADD COLUMN paid_at               DATETIME     NULL AFTER tracking_number,
    ADD INDEX idx_orders_stripe (stripe_session_id),
    ADD INDEX idx_orders_status (status, payment_method, created_at);
