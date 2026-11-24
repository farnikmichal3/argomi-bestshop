-- =============================================================================
-- ArgoMi BestShop · F5-18 · EAN for product variants
-- MySQL 8.0.16+ · InnoDB
--
-- EAN-8 and EAN-13 are optional variant identifiers. The application validates
-- the check digit; the database protects the accepted numeric lengths and
-- global uniqueness. NULL means that no EAN has been assigned.
-- =============================================================================

ALTER TABLE product_variant
    ADD COLUMN ean VARCHAR(13) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER sku,
    ADD UNIQUE KEY uq_product_variant_ean (ean),
    ADD CONSTRAINT ck_product_variant_ean_format
        CHECK (ean IS NULL OR ean REGEXP '^[0-9]{8}([0-9]{5})?$');
