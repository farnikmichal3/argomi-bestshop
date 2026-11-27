-- =============================================================================
-- ArgoMi BestShop · F5-25 · Allow shared image derivative associations
-- MySQL 8.0.16+ · InnoDB
--
-- product_image_derivative je katalogová vazba na již existující optimalizovaný
-- soubor. Více produktových vazeb může ukazovat na stejný storage path, pokud
-- sdílejí media_asset. Jedinečnost fyzického souboru nadále hlídá
-- media_asset_derivative.storage_key.
-- =============================================================================

ALTER TABLE product_image_derivative
    DROP INDEX uq_product_image_derivative_path;
