-- ArgoMi BestShop · Catalog category deletion messages (cs/en)
-- Idempotent seed; existing system and customized translations remain unchanged.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('categories.action', 'cs', 'Akce', 'core', 0, NULL, 0),
    ('categories.action', 'en', 'Actions', 'core', 0, NULL, 0),
    ('categories.delete.action', 'cs', 'Smazat', 'core', 0, NULL, 0),
    ('categories.delete.action', 'en', 'Delete', 'core', 0, NULL, 0),
    ('categories.delete.csrf', 'cs', 'Formulář pro odstranění kategorie vypršel. Obnov stránku a zkus to znovu.', 'core', 0, NULL, 0),
    ('categories.delete.csrf', 'en', 'The category deletion form expired. Refresh the page and try again.', 'core', 0, NULL, 0),
    ('categories.delete.deleted', 'cs', 'Kategorie byla odstraněna.', 'core', 0, NULL, 0),
    ('categories.delete.deleted', 'en', 'The category has been deleted.', 'core', 0, NULL, 0),
    ('categories.delete.has_children', 'cs', 'Kategorii nelze odstranit, protože obsahuje podkategorie. Nejprve přesuň nebo odstraň její podkategorie.', 'core', 0, NULL, 0),
    ('categories.delete.has_children', 'en', 'This category cannot be deleted because it contains subcategories. Move or remove its subcategories first.', 'core', 0, NULL, 0),
    ('categories.delete.has_products', 'cs', 'Kategorii nelze odstranit, protože je přiřazena k produktům. Nejprve změň jejich zařazení.', 'core', 0, NULL, 0),
    ('categories.delete.has_products', 'en', 'This category cannot be deleted because products are assigned to it. Reassign those products first.', 'core', 0, NULL, 0),
    ('categories.delete.not_found', 'cs', 'Kategorie již neexistuje nebo nebyla nalezena.', 'core', 0, NULL, 0),
    ('categories.delete.not_found', 'en', 'The category no longer exists or could not be found.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
