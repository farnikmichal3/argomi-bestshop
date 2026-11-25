-- =============================================================================
-- ArgoMi BestShop · F5-22 · Media library foundation
-- MySQL 8.0.16+ · InnoDB
--
-- Přesun identity mediálního souboru mimo produktovou vazbu.
-- Existující product_image záznamy se zachovají a dostanou odpovídající
-- media_asset se stejným ID; fyzické soubory se nepřesouvají.
-- =============================================================================

CREATE TABLE media_asset (
    id                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    original_filename        VARCHAR(255) NOT NULL,
    storage_key              VARCHAR(500) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    source_sha256            CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    source_mime_type         VARCHAR(100) NULL,
    source_width             INT UNSIGNED NULL,
    source_height            INT UNSIGNED NULL,
    source_file_size_bytes   BIGINT UNSIGNED NULL,
    created_at               DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_media_asset_storage_key (storage_key),
    KEY idx_media_asset_created (created_at, id),
    CONSTRAINT ck_media_asset_metadata_complete CHECK (
        (source_sha256 IS NULL AND source_mime_type IS NULL AND source_width IS NULL
            AND source_height IS NULL AND source_file_size_bytes IS NULL)
        OR
        (source_sha256 IS NOT NULL AND source_mime_type IS NOT NULL AND source_width > 0
            AND source_height > 0 AND source_file_size_bytes > 0)
    ),
    CONSTRAINT ck_media_asset_sha256 CHECK (
        source_sha256 IS NULL OR source_sha256 REGEXP '^[0-9a-f]{64}$'
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE media_asset_derivative (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    media_asset_id     BIGINT UNSIGNED NOT NULL,
    variant            ENUM('thumbnail', 'listing', 'detail') NOT NULL,
    format             ENUM('webp', 'avif') NOT NULL,
    source_sha256      CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    recipe_sha256      CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    width              INT UNSIGNED NOT NULL,
    height             INT UNSIGNED NOT NULL,
    storage_key        VARCHAR(500) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    file_size_bytes    BIGINT UNSIGNED NOT NULL,
    created_at         DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_media_asset_derivative_identity (media_asset_id, variant, format),
    UNIQUE KEY uq_media_asset_derivative_storage_key (storage_key),
    KEY idx_media_asset_derivative_lookup (media_asset_id, format, width, height),
    CONSTRAINT fk_media_asset_derivative_asset FOREIGN KEY (media_asset_id)
        REFERENCES media_asset (id) ON DELETE CASCADE,
    CONSTRAINT ck_media_asset_derivative_width CHECK (width > 0),
    CONSTRAINT ck_media_asset_derivative_height CHECK (height > 0),
    CONSTRAINT ck_media_asset_derivative_file_size CHECK (file_size_bytes > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Backfill existing catalog images without changing their files or IDs.
INSERT INTO media_asset (
    id, original_filename, storage_key, source_sha256, source_mime_type,
    source_width, source_height, source_file_size_bytes
)
SELECT
    id,
    CONCAT('legacy-', id),
    file_path,
    source_sha256,
    source_mime_type,
    source_width,
    source_height,
    source_file_size_bytes
FROM product_image;

INSERT INTO media_asset_derivative (
    media_asset_id, variant, format, source_sha256, recipe_sha256,
    width, height, storage_key, file_size_bytes
)
SELECT
    product_image_id,
    CASE
        WHEN file_path LIKE '%/thumbnail.webp' THEN 'thumbnail'
        WHEN file_path LIKE '%/listing.webp' THEN 'listing'
        ELSE 'detail'
    END,
    format,
    source_sha256,
    recipe_sha256,
    width,
    height,
    file_path,
    file_size_bytes
FROM product_image_derivative;

ALTER TABLE product_image
    ADD COLUMN media_asset_id BIGINT UNSIGNED NULL AFTER product_id;

UPDATE product_image
SET media_asset_id = id;

ALTER TABLE product_image
    MODIFY media_asset_id BIGINT UNSIGNED NOT NULL,
    ADD KEY idx_product_image_media_asset (media_asset_id),
    ADD CONSTRAINT fk_product_image_media_asset
        FOREIGN KEY (media_asset_id) REFERENCES media_asset (id) ON DELETE RESTRICT;
