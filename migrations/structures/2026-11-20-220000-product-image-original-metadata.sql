-- =============================================================================
-- ArgoMi BestShop · F5-08 · Metadata for preserved product image originals
-- MySQL 8.x · InnoDB
-- =============================================================================

ALTER TABLE product_image
    ADD COLUMN source_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    ADD COLUMN source_mime_type VARCHAR(100) NULL,
    ADD COLUMN source_width INT UNSIGNED NULL,
    ADD COLUMN source_height INT UNSIGNED NULL,
    ADD COLUMN source_file_size_bytes BIGINT UNSIGNED NULL,
    ADD CONSTRAINT ck_product_image_original_metadata_complete CHECK (
        (source_sha256 IS NULL AND source_mime_type IS NULL AND source_width IS NULL
            AND source_height IS NULL AND source_file_size_bytes IS NULL)
        OR
        (source_sha256 IS NOT NULL AND source_mime_type IS NOT NULL AND source_width > 0
            AND source_height > 0 AND source_file_size_bytes > 0)
    ),
    ADD CONSTRAINT ck_product_image_original_sha256 CHECK (
        source_sha256 IS NULL OR source_sha256 REGEXP '^[0-9a-f]{64}$'
    );
