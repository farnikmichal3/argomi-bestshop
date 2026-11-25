-- =============================================================================
-- ArgoMi BestShop · F5-22 · Product image management
-- MySQL 8.0.16+ · InnoDB
-- =============================================================================

ALTER TABLE product_image
    ADD COLUMN is_primary TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER sort_order,
    ADD COLUMN variant_scope_id BIGINT UNSIGNED
        GENERATED ALWAYS AS (COALESCE(product_variant_id, 0)) STORED,
    ADD COLUMN primary_slot TINYINT UNSIGNED
        GENERATED ALWAYS AS (CASE WHEN is_primary = 1 THEN 1 ELSE NULL END) STORED;

-- Existing groups receive a deterministic primary image (lowest ID).
UPDATE product_image AS image
JOIN (
    SELECT product_id, COALESCE(product_variant_id, 0) AS variant_scope_id, MIN(id) AS image_id
    FROM product_image
    GROUP BY product_id, COALESCE(product_variant_id, 0)
) AS first_image ON first_image.image_id = image.id
SET image.is_primary = 1;

ALTER TABLE product_image
    ADD UNIQUE KEY uq_product_image_primary_scope (product_id, variant_scope_id, primary_slot),
    ADD CONSTRAINT ck_product_image_is_primary CHECK (is_primary IN (0, 1));
