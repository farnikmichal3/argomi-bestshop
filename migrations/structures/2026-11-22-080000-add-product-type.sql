-- =============================================================================
-- ArgoMi BestShop · F5-15 · Add product type
-- MySQL 8.0.16+ · InnoDB
--
-- Product type describes the commercial/technical nature of a product.
-- Existing products are physical by default. Configurability is a separate
-- product capability and is intentionally not represented by this enum.
-- =============================================================================

ALTER TABLE product
    ADD COLUMN type ENUM('physical','digital','service') NOT NULL DEFAULT 'physical'
        AFTER code,
    ADD KEY idx_product_type (type);
