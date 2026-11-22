-- ArgoMi BestShop · Catalog category creation form messages (cs/en)
-- Idempotent seed; existing system and customized translations remain unchanged.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('categories.form.title', 'cs', 'Nová kategorie', 'core', 0, NULL, 0),
    ('categories.form.title', 'en', 'Create category', 'core', 0, NULL, 0),
    ('categories.form.parent', 'cs', 'Nadřazená kategorie', 'core', 0, NULL, 0),
    ('categories.form.parent', 'en', 'Parent category', 'core', 0, NULL, 0),
    ('categories.form.parent.none', 'cs', '— bez nadřazené kategorie —', 'core', 0, NULL, 0),
    ('categories.form.parent.none', 'en', '— no parent category —', 'core', 0, NULL, 0),
    ('categories.form.code', 'cs', 'Kód kategorie', 'core', 0, NULL, 0),
    ('categories.form.code', 'en', 'Category code', 'core', 0, NULL, 0),
    ('categories.form.sort_order', 'cs', 'Pořadí', 'core', 0, NULL, 0),
    ('categories.form.sort_order', 'en', 'Sort order', 'core', 0, NULL, 0),
    ('categories.form.active', 'cs', 'Kategorie je aktivní', 'core', 0, NULL, 0),
    ('categories.form.active', 'en', 'Category is active', 'core', 0, NULL, 0),
    ('categories.form.name', 'cs', 'Název', 'core', 0, NULL, 0),
    ('categories.form.name', 'en', 'Name', 'core', 0, NULL, 0),
    ('categories.form.description', 'cs', 'Popis', 'core', 0, NULL, 0),
    ('categories.form.description', 'en', 'Description', 'core', 0, NULL, 0),
    ('categories.form.submit', 'cs', 'Vytvořit kategorii', 'core', 0, NULL, 0),
    ('categories.form.submit', 'en', 'Create category', 'core', 0, NULL, 0),
    ('categories.form.created', 'cs', 'Kategorie byla vytvořena.', 'core', 0, NULL, 0),
    ('categories.form.created', 'en', 'Category has been created.', 'core', 0, NULL, 0),
    ('categories.form.error.required', 'cs', 'Vyplň povinné pole.', 'core', 0, NULL, 0),
    ('categories.form.error.required', 'en', 'This field is required.', 'core', 0, NULL, 0),
    ('categories.form.error.sort_order', 'cs', 'Pořadí musí být nezáporné.', 'core', 0, NULL, 0),
    ('categories.form.error.sort_order', 'en', 'Sort order must be zero or greater.', 'core', 0, NULL, 0),
    ('categories.form.error.invalid', 'cs', 'Zadané údaje nejsou platné.', 'core', 0, NULL, 0),
    ('categories.form.error.invalid', 'en', 'The submitted data is invalid.', 'core', 0, NULL, 0),
    ('categories.form.error.duplicate', 'cs', 'Kategorie s tímto kódem už existuje.', 'core', 0, NULL, 0),
    ('categories.form.error.duplicate', 'en', 'A category with this code already exists.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
