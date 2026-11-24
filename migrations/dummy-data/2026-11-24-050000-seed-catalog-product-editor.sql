-- ArgoMi BestShop · F5-16 · Basic product editor messages (cs/en)
-- Idempotent seed; existing system and customized translations remain unchanged.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('products.admin.action.create', 'cs', 'Nový produkt', 'core', 0, NULL, 0),
    ('products.admin.action.create', 'en', 'New product', 'core', 0, NULL, 0),
    ('products.admin.action.edit', 'cs', 'Upravit', 'core', 0, NULL, 0),
    ('products.admin.action.edit', 'en', 'Edit', 'core', 0, NULL, 0),
    ('products.editor.create_title', 'cs', 'Nový produkt', 'core', 0, NULL, 0),
    ('products.editor.create_title', 'en', 'New product', 'core', 0, NULL, 0),
    ('products.editor.edit_title', 'cs', 'Upravit produkt', 'core', 0, NULL, 0),
    ('products.editor.edit_title', 'en', 'Edit product', 'core', 0, NULL, 0),
    ('products.editor.code', 'cs', 'Kód produktu', 'core', 0, NULL, 0),
    ('products.editor.code', 'en', 'Product code', 'core', 0, NULL, 0),
    ('products.editor.status', 'cs', 'Stav produktu', 'core', 0, NULL, 0),
    ('products.editor.status', 'en', 'Product status', 'core', 0, NULL, 0),
    ('products.editor.type', 'cs', 'Typ produktu', 'core', 0, NULL, 0),
    ('products.editor.type', 'en', 'Product type', 'core', 0, NULL, 0),
    ('products.editor.tax_class', 'cs', 'Daňová třída', 'core', 0, NULL, 0),
    ('products.editor.tax_class', 'en', 'Tax class', 'core', 0, NULL, 0),
    ('products.editor.categories', 'cs', 'Zařazení do kategorií', 'core', 0, NULL, 0),
    ('products.editor.categories', 'en', 'Categories', 'core', 0, NULL, 0),
    ('products.editor.name', 'cs', 'Název', 'core', 0, NULL, 0),
    ('products.editor.name', 'en', 'Name', 'core', 0, NULL, 0),
    ('products.editor.short_description', 'cs', 'Krátký popis', 'core', 0, NULL, 0),
    ('products.editor.short_description', 'en', 'Short description', 'core', 0, NULL, 0),
    ('products.editor.description', 'cs', 'Popis produktu', 'core', 0, NULL, 0),
    ('products.editor.description', 'en', 'Product description', 'core', 0, NULL, 0),
    ('products.editor.meta_title', 'cs', 'SEO titulek', 'core', 0, NULL, 0),
    ('products.editor.meta_title', 'en', 'SEO title', 'core', 0, NULL, 0),
    ('products.editor.meta_description', 'cs', 'SEO popis', 'core', 0, NULL, 0),
    ('products.editor.meta_description', 'en', 'SEO description', 'core', 0, NULL, 0),
    ('products.editor.submit', 'cs', 'Uložit produkt', 'core', 0, NULL, 0),
    ('products.editor.submit', 'en', 'Save product', 'core', 0, NULL, 0),
    ('products.editor.cancel', 'cs', 'Zpět na seznam', 'core', 0, NULL, 0),
    ('products.editor.cancel', 'en', 'Back to list', 'core', 0, NULL, 0),
    ('products.editor.created', 'cs', 'Produkt byl vytvořen.', 'core', 0, NULL, 0),
    ('products.editor.created', 'en', 'Product was created.', 'core', 0, NULL, 0),
    ('products.editor.updated', 'cs', 'Produkt byl uložen.', 'core', 0, NULL, 0),
    ('products.editor.updated', 'en', 'Product was saved.', 'core', 0, NULL, 0),
    ('products.editor.not_found', 'cs', 'Produkt nebyl nalezen.', 'core', 0, NULL, 0),
    ('products.editor.not_found', 'en', 'Product was not found.', 'core', 0, NULL, 0),
    ('products.editor.error.required', 'cs', 'Vyplň povinné pole.', 'core', 0, NULL, 0),
    ('products.editor.error.required', 'en', 'This field is required.', 'core', 0, NULL, 0),
    ('products.editor.error.invalid', 'cs', 'Zadané údaje nejsou platné.', 'core', 0, NULL, 0),
    ('products.editor.error.invalid', 'en', 'The submitted data is invalid.', 'core', 0, NULL, 0),
    ('products.editor.error.duplicate', 'cs', 'Produkt s tímto kódem nebo slugem již existuje.', 'core', 0, NULL, 0),
    ('products.editor.error.duplicate', 'en', 'A product with this code or slug already exists.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
