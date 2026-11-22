-- =============================================================================
-- ArgoMi BestShop · F5-15 · Protect category deletion
-- MySQL 8.0.16+ · InnoDB
--
-- Product assignments must not disappear when a category is deleted.
-- Drop and recreate the FK in separate statements: MySQL may reject reusing
-- the same constraint name inside a single ALTER TABLE statement.
-- =============================================================================

ALTER TABLE product_category
    DROP FOREIGN KEY fk_product_category_category;

ALTER TABLE product_category
    ADD CONSTRAINT fk_product_category_category
        FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE RESTRICT;
