-- ArgoMi BestShop · F5-17 · Matrix size limit message (cs/en)
-- Idempotent seed; existing system and customized translations remain unchanged.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('products.variants.limit', 'cs', 'Matice obsahuje příliš mnoho kombinací pro bezpečné zobrazení. Zmenši počet hodnot os.', 'core', 0, NULL, 0),
    ('products.variants.limit', 'en', 'The matrix contains too many combinations to display safely. Reduce the number of axis values.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
