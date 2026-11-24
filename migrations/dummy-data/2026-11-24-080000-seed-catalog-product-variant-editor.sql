-- ArgoMi BestShop · F5-18 · Product variant editor messages (cs/en)
-- Idempotent seed; existing system and customized translations remain unchanged.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('products.variant_editor.title', 'cs', 'Úprava varianty', 'core', 0, NULL, 0),
    ('products.variant_editor.title', 'en', 'Edit variant', 'core', 0, NULL, 0),
    ('products.variant_editor.sku', 'cs', 'SKU', 'core', 0, NULL, 0),
    ('products.variant_editor.sku', 'en', 'SKU', 'core', 0, NULL, 0),
    ('products.variant_editor.ean', 'cs', 'EAN (EAN-8 nebo EAN-13)', 'core', 0, NULL, 0),
    ('products.variant_editor.ean', 'en', 'EAN (EAN-8 or EAN-13)', 'core', 0, NULL, 0),
    ('products.variant_editor.ean_hint', 'cs', 'Zadej pouze číslice včetně platné kontrolní číslice. Pole může zůstat prázdné.', 'core', 0, NULL, 0),
    ('products.variant_editor.ean_hint', 'en', 'Enter digits only, including a valid check digit. This field may be left empty.', 'core', 0, NULL, 0),
    ('products.variant_editor.weight', 'cs', 'Hmotnost (g)', 'core', 0, NULL, 0),
    ('products.variant_editor.weight', 'en', 'Weight (g)', 'core', 0, NULL, 0),
    ('products.variant_editor.weight_hint', 'cs', 'Nepovinná hmotnost varianty v celých gramech.', 'core', 0, NULL, 0),
    ('products.variant_editor.weight_hint', 'en', 'Optional variant weight in whole grams.', 'core', 0, NULL, 0),
    ('products.variant_editor.default', 'cs', 'Výchozí varianta produktu', 'core', 0, NULL, 0),
    ('products.variant_editor.default', 'en', 'Default product variant', 'core', 0, NULL, 0),
    ('products.variant_editor.inactive', 'cs', 'Neaktivní varianta nemůže být nastavena jako výchozí.', 'core', 0, NULL, 0),
    ('products.variant_editor.inactive', 'en', 'An inactive variant cannot be set as the default.', 'core', 0, NULL, 0),
    ('products.variant_editor.save', 'cs', 'Uložit variantu', 'core', 0, NULL, 0),
    ('products.variant_editor.save', 'en', 'Save variant', 'core', 0, NULL, 0),
    ('products.variant_editor.cancel', 'cs', 'Zpět na matici variant', 'core', 0, NULL, 0),
    ('products.variant_editor.cancel', 'en', 'Back to variant matrix', 'core', 0, NULL, 0),
    ('products.variant_editor.not_found', 'cs', 'Varianta nebyla nalezena.', 'core', 0, NULL, 0),
    ('products.variant_editor.not_found', 'en', 'The variant was not found.', 'core', 0, NULL, 0),
    ('products.variant_editor.updated', 'cs', 'Údaje varianty byly uloženy.', 'core', 0, NULL, 0),
    ('products.variant_editor.updated', 'en', 'Variant details have been saved.', 'core', 0, NULL, 0),
    ('products.variant_editor.error.duplicate', 'cs', 'SKU nebo EAN již používá jiná varianta.', 'core', 0, NULL, 0),
    ('products.variant_editor.error.duplicate', 'en', 'The SKU or EAN is already used by another variant.', 'core', 0, NULL, 0),
    ('products.variant_editor.error.invalid', 'cs', 'Údaje varianty nejsou platné. Zkontroluj SKU, EAN a hmotnost.', 'core', 0, NULL, 0),
    ('products.variant_editor.error.invalid', 'en', 'Variant details are invalid. Check the SKU, EAN and weight.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
