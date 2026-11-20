-- =============================================================================
-- ArgoMi BestShop · F4-17 · Dedicated Admin system-message domain
-- MySQL 8.0.16+ · InnoDB
--
-- Texty administrace Translation jsou vlastnictvím domény Admin.
-- Nejsou součástí Catalog, Cart ani Checkout.
-- Hodnoty budou dodány samostatným idempotentním seedem.
-- =============================================================================

CREATE TABLE admin_message_translation (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    message_key     VARCHAR(190) NOT NULL,
    locale          VARCHAR(10) NOT NULL,
    message_text    TEXT NOT NULL,
    source          ENUM('core','custom') NOT NULL DEFAULT 'core',
    is_customized   TINYINT(1) NOT NULL DEFAULT 0,
    default_hash    CHAR(40) NULL,
    is_outdated     TINYINT(1) NOT NULL DEFAULT 0,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by      BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_message_translation (locale, message_key),
    KEY idx_admin_message_translation_review (is_outdated),
    CONSTRAINT fk_admin_message_translation_language
        FOREIGN KEY (locale) REFERENCES language (code)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
