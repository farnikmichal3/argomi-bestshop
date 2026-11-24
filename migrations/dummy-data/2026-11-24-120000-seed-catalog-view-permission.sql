-- ArgoMi BestShop · F5-26 · Read-only catalog navigation (cs/en)
-- Idempotent seed; existing system and customized translations remain unchanged.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('products.admin.action.variants', 'cs', 'Varianty', 'core', 0, NULL, 0),
    ('products.admin.action.variants', 'en', 'Variants', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
