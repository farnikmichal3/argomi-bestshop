-- ArgoMi BestShop · F5-22 · Product image management form
-- Idempotent system translations.

SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('products.images.form.title', 'cs', 'Upravit přiřazený obrázek', 'core', 0, NULL, 0),
    ('products.images.form.title', 'en', 'Manage assigned image', 'core', 0, NULL, 0),
    ('products.images.form.image', 'cs', 'Obrázek', 'core', 0, NULL, 0),
    ('products.images.form.image', 'en', 'Image', 'core', 0, NULL, 0),
    ('products.images.form.alt_text', 'cs', 'Alternativní text (ALT)', 'core', 0, NULL, 0),
    ('products.images.form.alt_text', 'en', 'Alternative text (ALT)', 'core', 0, NULL, 0),
    ('products.images.form.sort_order', 'cs', 'Pořadí', 'core', 0, NULL, 0),
    ('products.images.form.sort_order', 'en', 'Sort order', 'core', 0, NULL, 0),
    ('products.images.form.action', 'cs', 'Akce', 'core', 0, NULL, 0),
    ('products.images.form.action', 'en', 'Action', 'core', 0, NULL, 0),
    ('products.images.form.action.update', 'cs', 'Uložit ALT text a pořadí', 'core', 0, NULL, 0),
    ('products.images.form.action.update', 'en', 'Save ALT text and order', 'core', 0, NULL, 0),
    ('products.images.form.action.primary', 'cs', 'Nastavit jako hlavní', 'core', 0, NULL, 0),
    ('products.images.form.action.primary', 'en', 'Set as primary', 'core', 0, NULL, 0),
    ('products.images.form.action.remove', 'cs', 'Odebrat z produktu', 'core', 0, NULL, 0),
    ('products.images.form.action.remove', 'en', 'Remove from product', 'core', 0, NULL, 0),
    ('products.images.form.submit', 'cs', 'Provést akci', 'core', 0, NULL, 0),
    ('products.images.form.submit', 'en', 'Apply action', 'core', 0, NULL, 0),
    ('products.images.form.error.invalid', 'cs', 'Zadané údaje nejsou platné.', 'core', 0, NULL, 0),
    ('products.images.form.error.invalid', 'en', 'The submitted data is invalid.', 'core', 0, NULL, 0),
    ('products.images.form.updated', 'cs', 'Údaje obrázku byly uloženy.', 'core', 0, NULL, 0),
    ('products.images.form.updated', 'en', 'Image details were saved.', 'core', 0, NULL, 0),
    ('products.images.form.primary_set', 'cs', 'Hlavní obrázek byl změněn.', 'core', 0, NULL, 0),
    ('products.images.form.primary_set', 'en', 'Primary image was changed.', 'core', 0, NULL, 0),
    ('products.images.form.removed', 'cs', 'Obrázek byl odebrán z produktu. V knihovně médií zůstává zachován.', 'core', 0, NULL, 0),
    ('products.images.form.removed', 'en', 'The image was removed from the product. It remains in the media library.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE id = id;

COMMIT;
