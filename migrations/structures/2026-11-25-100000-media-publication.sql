-- =============================================================================
-- ArgoMi BestShop · F5-22 · Explicit media publication
-- MySQL 8.0.16+ · InnoDB
--
-- Media assets are private by default. A random opaque public identifier is
-- assigned only when an administrator explicitly publishes an asset.
-- Unpublishing is controlled by published_at; the identifier is not an
-- authorization secret and must never bypass the publication-state check.
-- Existing/backfilled assets remain private.
-- =============================================================================

ALTER TABLE media_asset
    ADD COLUMN public_token CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL
        AFTER source_file_size_bytes,
    ADD COLUMN published_at DATETIME(6) NULL
        AFTER public_token,
    ADD UNIQUE KEY uq_media_asset_public_token (public_token),
    ADD KEY idx_media_asset_publication (published_at, id),
    ADD CONSTRAINT ck_media_asset_public_token CHECK (
        public_token IS NULL OR public_token REGEXP '^[0-9a-f]{64}$'
    ),
    ADD CONSTRAINT ck_media_asset_publication_state CHECK (
        published_at IS NULL OR public_token IS NOT NULL
    );
