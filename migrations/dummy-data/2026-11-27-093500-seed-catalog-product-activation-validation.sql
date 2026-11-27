-- ArgoMi BestShop · F5-24 · Product activation validation messages (cs/en)
-- Idempotent seed; existing system and customized translations remain unchanged.

SET NAMES utf8mb4;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('products.editor.error.activation_requirements', 'cs', 'Produkt nelze aktivovat. Nejdříve vytvoř alespoň jednu aktivní variantu a nastav ji jako výchozí.', 'core', 0, NULL, 0),
    ('products.editor.error.activation_requirements', 'en', 'The product cannot be activated. Create at least one active variant and set it as the default first.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;
