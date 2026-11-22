-- ArgoMi BestShop · Category editor messages (cs/en)
-- Idempotent system translation seed.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('categories.action.edit', 'cs', 'Upravit', 'core', 0, NULL, 0),
    ('categories.action.edit', 'en', 'Edit', 'core', 0, NULL, 0),
    ('categories.edit.title', 'cs', 'Upravit kategorii', 'core', 0, NULL, 0),
    ('categories.edit.title', 'en', 'Edit category', 'core', 0, NULL, 0),
    ('categories.form.edit.submit', 'cs', 'Uložit změny', 'core', 0, NULL, 0),
    ('categories.form.edit.submit', 'en', 'Save changes', 'core', 0, NULL, 0),
    ('categories.form.updated', 'cs', 'Kategorie byla upravena.', 'core', 0, NULL, 0),
    ('categories.form.updated', 'en', 'Category has been updated.', 'core', 0, NULL, 0),
    ('categories.edit.cancel', 'cs', 'Zpět na přehled', 'core', 0, NULL, 0),
    ('categories.edit.cancel', 'en', 'Back to categories', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
