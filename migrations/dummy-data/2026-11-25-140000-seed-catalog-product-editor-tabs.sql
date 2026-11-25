-- ArgoMi BestShop · F5-39 · Product editor tabs (cs/en)
-- Idempotent system translations; customized values are never overwritten.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('products.editor.basic_data', 'cs', 'Základní údaje', 'core', 0, NULL, 0),
    ('products.editor.basic_data', 'en', 'Basic details', 'core', 0, NULL, 0),
    ('products.editor.basic_data_description', 'cs', 'Kód, stav, typ, zařazení do kategorií a lokalizovaný obsah produktu.', 'core', 0, NULL, 0),
    ('products.editor.basic_data_description', 'en', 'Product code, status, type, categories and localized product content.', 'core', 0, NULL, 0),
    ('products.variants.manage_description', 'cs', 'Zobraz existující kombinace, vytvoř chybějící varianty a otevři detail konkrétní varianty.', 'core', 0, NULL, 0),
    ('products.variants.manage_description', 'en', 'Review existing combinations, generate missing variants and open an individual variant.', 'core', 0, NULL, 0),
    ('products.variants.manage_button', 'cs', 'Otevřít správu variant', 'core', 0, NULL, 0),
    ('products.variants.manage_button', 'en', 'Open variant management', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
