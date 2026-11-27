-- ArgoMi BestShop · F5-22 · Catalog-owned HugeRTE media picker messages
-- The product editor belongs to TranslationDomain::Catalog.
-- This corrective seed places the picker messages in catalog_message_translation.
-- Existing system/custom translations are never overwritten.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('products.editor.media_picker.title', 'cs', 'Vybrat publikovaný obrázek', 'core', 0, NULL, 0),
    ('products.editor.media_picker.title', 'en', 'Select a published image', 'core', 0, NULL, 0),
    ('products.editor.media_picker.label', 'cs', 'Obrázek', 'core', 0, NULL, 0),
    ('products.editor.media_picker.label', 'en', 'Image', 'core', 0, NULL, 0),
    ('products.editor.media_picker.cancel', 'cs', 'Zrušit', 'core', 0, NULL, 0),
    ('products.editor.media_picker.cancel', 'en', 'Cancel', 'core', 0, NULL, 0),
    ('products.editor.media_picker.insert', 'cs', 'Vložit', 'core', 0, NULL, 0),
    ('products.editor.media_picker.insert', 'en', 'Insert', 'core', 0, NULL, 0),
    ('products.editor.media_picker.unavailable', 'cs', 'Knihovna publikovaných médií není dostupná.', 'core', 0, NULL, 0),
    ('products.editor.media_picker.unavailable', 'en', 'The published media library is unavailable.', 'core', 0, NULL, 0),
    ('products.editor.media_picker.empty', 'cs', 'Knihovna neobsahuje žádné publikované obrázky.', 'core', 0, NULL, 0),
    ('products.editor.media_picker.empty', 'en', 'The library contains no published images.', 'core', 0, NULL, 0),
    ('products.editor.media_picker.failed', 'cs', 'Obrázky se nepodařilo načíst. Zkontroluj oprávnění a zkus to znovu.', 'core', 0, NULL, 0),
    ('products.editor.media_picker.failed', 'en', 'Images could not be loaded. Check permissions and try again.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
