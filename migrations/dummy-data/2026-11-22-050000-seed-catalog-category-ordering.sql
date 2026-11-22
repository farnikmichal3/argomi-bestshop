-- ArgoMi BestShop · Catalog category ordering messages (cs/en)
-- Idempotent seed; existing system and customized translations remain unchanged.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('categories.order.title', 'cs', 'Řazení kategorií', 'core', 0, NULL, 0),
    ('categories.order.title', 'en', 'Category ordering', 'core', 0, NULL, 0),
    ('categories.order.help', 'cs', 'Přetáhni kategorii v rámci stejné úrovně a potom ulož nové pořadí.', 'core', 0, NULL, 0),
    ('categories.order.help', 'en', 'Drag a category within the same level, then save the new order.', 'core', 0, NULL, 0),
    ('categories.order.root', 'cs', 'Hlavní kategorie', 'core', 0, NULL, 0),
    ('categories.order.root', 'en', 'Top-level categories', 'core', 0, NULL, 0),
    ('categories.order.parent', 'cs', 'Pod kategorií', 'core', 0, NULL, 0),
    ('categories.order.parent', 'en', 'Under category', 'core', 0, NULL, 0),
    ('categories.order.save', 'cs', 'Uložit pořadí', 'core', 0, NULL, 0),
    ('categories.order.save', 'en', 'Save order', 'core', 0, NULL, 0),
    ('categories.order.drag', 'cs', 'Přetáhnout pro změnu pořadí', 'core', 0, NULL, 0),
    ('categories.order.drag', 'en', 'Drag to change order', 'core', 0, NULL, 0),
    ('categories.order.saved', 'cs', 'Pořadí kategorií bylo uloženo.', 'core', 0, NULL, 0),
    ('categories.order.saved', 'en', 'Category order has been saved.', 'core', 0, NULL, 0),
    ('categories.order.error', 'cs', 'Pořadí kategorií se nepodařilo uložit. Obnov stránku a zkus to znovu.', 'core', 0, NULL, 0),
    ('categories.order.error', 'en', 'The category order could not be saved. Refresh the page and try again.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
