-- ArgoMi BestShop · F5-25 · Product lifecycle messages (cs/en)
-- Idempotent seed; existing system and customized translations remain unchanged.

SET NAMES utf8mb4;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('products.lifecycle.archive.action', 'cs', 'Archivovat', 'core', 0, NULL, 0),
    ('products.lifecycle.archive.action', 'en', 'Archive', 'core', 0, NULL, 0),
    ('products.lifecycle.archive.title', 'cs', 'Archivace produktu', 'core', 0, NULL, 0),
    ('products.lifecycle.archive.title', 'en', 'Archive product', 'core', 0, NULL, 0),
    ('products.lifecycle.archive.confirm', 'cs', 'Ano, nevratně archivovat produkt', 'core', 0, NULL, 0),
    ('products.lifecycle.archive.confirm', 'en', 'Yes, archive this product permanently', 'core', 0, NULL, 0),
    ('products.lifecycle.archive.success', 'cs', 'Produkt byl archivován. Jeho aktivní varianty byly deaktivovány.', 'core', 0, NULL, 0),
    ('products.lifecycle.archive.success', 'en', 'The product was archived. Its active variants were deactivated.', 'core', 0, NULL, 0),
    ('products.lifecycle.archive.warning', 'cs', 'Archivace je nevratná. Produkt zůstane zachován kvůli historii a nebude možné jej znovu aktivovat. Všechny aktivní varianty budou deaktivovány.', 'core', 0, NULL, 0),
    ('products.lifecycle.archive.warning', 'en', 'Archiving is permanent. The product will be retained for history and cannot be reactivated. All active variants will be deactivated.', 'core', 0, NULL, 0),
    ('products.lifecycle.archive.already', 'cs', 'Tento produkt je již archivovaný. Zpět jej aktivovat nelze.', 'core', 0, NULL, 0),
    ('products.lifecycle.archive.already', 'en', 'This product is already archived and cannot be reactivated.', 'core', 0, NULL, 0),
    ('products.lifecycle.error.not_found', 'cs', 'Produkt nebyl nalezen.', 'core', 0, NULL, 0),
    ('products.lifecycle.error.not_found', 'en', 'The product was not found.', 'core', 0, NULL, 0),
    ('products.lifecycle.error.invalid', 'cs', 'Akci nelze provést. Obnov stránku a zkontroluj stav produktu.', 'core', 0, NULL, 0),
    ('products.lifecycle.error.invalid', 'en', 'The action cannot be completed. Refresh the page and check the product status.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;
