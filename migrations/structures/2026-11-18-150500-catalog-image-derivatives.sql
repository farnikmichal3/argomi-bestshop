-- =============================================================================
-- ArgoMi BestShop · F5-08 · Catalog image derivatives
-- MySQL 8.0.16+ · InnoDB
--
-- Originální soubory zůstávají beze změny v product_image.file_path.
-- Tato tabulka eviduje pouze znovu vytvořitelné optimalizované soubory.
-- source_sha256 identifikuje přesný obsah originálu; recipe_sha256 identifikuje
-- konfiguraci transformace (rozměry, ořez, kvalitu a další parametry).
-- =============================================================================

CREATE TABLE product_image_derivative (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_image_id   BIGINT UNSIGNED NOT NULL,
    format             ENUM('webp', 'avif') NOT NULL,
    source_sha256      CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    recipe_sha256      CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    width              INT UNSIGNED NOT NULL,
    height             INT UNSIGNED NOT NULL,
    file_path          VARCHAR(500) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    file_size_bytes    BIGINT UNSIGNED NOT NULL,
    created_at         DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_image_derivative_identity
        (product_image_id, source_sha256, recipe_sha256, format),
    UNIQUE KEY uq_product_image_derivative_path (file_path),
    KEY idx_product_image_derivative_lookup
        (product_image_id, format, width, height),
    CONSTRAINT fk_product_image_derivative_image
        FOREIGN KEY (product_image_id)
        REFERENCES product_image (id)
        ON DELETE CASCADE,
    CONSTRAINT ck_product_image_derivative_width CHECK (width > 0),
    CONSTRAINT ck_product_image_derivative_height CHECK (height > 0),
    CONSTRAINT ck_product_image_derivative_file_size CHECK (file_size_bytes > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
