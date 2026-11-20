-- =============================================================================
-- ArgoMi BestShop · F3-31 · Split system messages by owning domain
-- MySQL 8.0.16+ · InnoDB
--
-- Doménové texty se přesouvají do vlastních tabulek. Zdrojová tabulka se
-- odstraní až po ověření počtu a úplné shody všech přenesených sloupců.
-- Historické migrace ani seed skripty se zpětně neupravují.
--
-- MySQL DDL implicitně potvrzuje transakce. Bezpečnost převodu proto stojí na
-- pořadí CREATE → COPY → ASSERT → DROP; při chybě zůstává zdrojová tabulka.
-- =============================================================================

-- Neznámá doména by se nesměla ztratit při rozdělení.
DELIMITER $$

CREATE PROCEDURE assert_system_message_domains()
BEGIN
    IF EXISTS (
        SELECT 1
        FROM translation_message
        WHERE domain NOT IN ('catalog', 'cart', 'checkout')
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Unexpected translation_message domain; split aborted.';
    END IF;
END$$

CALL assert_system_message_domains()$$

DELIMITER ;

DROP PROCEDURE assert_system_message_domains;

CREATE TABLE catalog_message_translation (
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
    UNIQUE KEY uq_catalog_message_translation (locale, message_key),
    KEY idx_catalog_message_translation_review (is_outdated),
    CONSTRAINT fk_catalog_message_translation_language
        FOREIGN KEY (locale) REFERENCES language (code)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE cart_message_translation (
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
    UNIQUE KEY uq_cart_message_translation (locale, message_key),
    KEY idx_cart_message_translation_review (is_outdated),
    CONSTRAINT fk_cart_message_translation_language
        FOREIGN KEY (locale) REFERENCES language (code)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE checkout_message_translation (
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
    UNIQUE KEY uq_checkout_message_translation (locale, message_key),
    KEY idx_checkout_message_translation_review (is_outdated),
    CONSTRAINT fk_checkout_message_translation_language
        FOREIGN KEY (locale) REFERENCES language (code)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ID i veškeré hodnoty se kopírují beze změny; NULL hash zůstává NULL.
INSERT INTO catalog_message_translation
    (id, message_key, locale, message_text, source, is_customized,
     default_hash, is_outdated, updated_at, updated_by)
SELECT
    id, message_key, locale, message_text, source, is_customized,
    default_hash, is_outdated, updated_at, updated_by
FROM translation_message
WHERE domain = 'catalog';

INSERT INTO cart_message_translation
    (id, message_key, locale, message_text, source, is_customized,
     default_hash, is_outdated, updated_at, updated_by)
SELECT
    id, message_key, locale, message_text, source, is_customized,
    default_hash, is_outdated, updated_at, updated_by
FROM translation_message
WHERE domain = 'cart';

INSERT INTO checkout_message_translation
    (id, message_key, locale, message_text, source, is_customized,
     default_hash, is_outdated, updated_at, updated_by)
SELECT
    id, message_key, locale, message_text, source, is_customized,
    default_hash, is_outdated, updated_at, updated_by
FROM translation_message
WHERE domain = 'checkout';

-- Před odstraněním zdroje ověříme počet i každý přenášený sloupec.
DELIMITER $$

CREATE PROCEDURE assert_system_message_split()
BEGIN
    IF (SELECT COUNT(*) FROM translation_message WHERE domain = 'catalog')
       <> (SELECT COUNT(*) FROM catalog_message_translation)
       OR EXISTS (
           SELECT 1
           FROM translation_message old
           LEFT JOIN catalog_message_translation new ON new.id = old.id
           WHERE old.domain = 'catalog'
             AND (
                 new.id IS NULL
                 OR NOT (old.message_key <=> new.message_key)
                 OR NOT (old.locale <=> new.locale)
                 OR NOT (old.message_text <=> new.message_text)
                 OR NOT (old.source <=> new.source)
                 OR NOT (old.is_customized <=> new.is_customized)
                 OR NOT (old.default_hash <=> new.default_hash)
                 OR NOT (old.is_outdated <=> new.is_outdated)
                 OR NOT (old.updated_at <=> new.updated_at)
                 OR NOT (old.updated_by <=> new.updated_by)
             )
       )
    THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Catalog message split verification failed; source retained.';
    END IF;

    IF (SELECT COUNT(*) FROM translation_message WHERE domain = 'cart')
       <> (SELECT COUNT(*) FROM cart_message_translation)
       OR EXISTS (
           SELECT 1
           FROM translation_message old
           LEFT JOIN cart_message_translation new ON new.id = old.id
           WHERE old.domain = 'cart'
             AND (
                 new.id IS NULL
                 OR NOT (old.message_key <=> new.message_key)
                 OR NOT (old.locale <=> new.locale)
                 OR NOT (old.message_text <=> new.message_text)
                 OR NOT (old.source <=> new.source)
                 OR NOT (old.is_customized <=> new.is_customized)
                 OR NOT (old.default_hash <=> new.default_hash)
                 OR NOT (old.is_outdated <=> new.is_outdated)
                 OR NOT (old.updated_at <=> new.updated_at)
                 OR NOT (old.updated_by <=> new.updated_by)
             )
       )
    THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cart message split verification failed; source retained.';
    END IF;

    IF (SELECT COUNT(*) FROM translation_message WHERE domain = 'checkout')
       <> (SELECT COUNT(*) FROM checkout_message_translation)
       OR EXISTS (
           SELECT 1
           FROM translation_message old
           LEFT JOIN checkout_message_translation new ON new.id = old.id
           WHERE old.domain = 'checkout'
             AND (
                 new.id IS NULL
                 OR NOT (old.message_key <=> new.message_key)
                 OR NOT (old.locale <=> new.locale)
                 OR NOT (old.message_text <=> new.message_text)
                 OR NOT (old.source <=> new.source)
                 OR NOT (old.is_customized <=> new.is_customized)
                 OR NOT (old.default_hash <=> new.default_hash)
                 OR NOT (old.is_outdated <=> new.is_outdated)
                 OR NOT (old.updated_at <=> new.updated_at)
                 OR NOT (old.updated_by <=> new.updated_by)
             )
       )
    THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Checkout message split verification failed; source retained.';
    END IF;

    IF (SELECT COUNT(*) FROM translation_message)
       <> (
           (SELECT COUNT(*) FROM catalog_message_translation)
           + (SELECT COUNT(*) FROM cart_message_translation)
           + (SELECT COUNT(*) FROM checkout_message_translation)
       )
    THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Total system message count changed; source retained.';
    END IF;
END$$

CALL assert_system_message_split()$$

DELIMITER ;

DROP PROCEDURE assert_system_message_split;

-- Zdroj se maže až po úspěšné kontrole všech 18 současných záznamů
-- (a libovolného budoucího počtu záznamů ve třech povolených doménách).
DROP TABLE translation_message;
