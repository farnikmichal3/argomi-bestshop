-- ArgoMi BestShop · F5-22 · Product image assignment messages
-- MySQL 8.0.16+ · Idempotent system translations.

SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO admin_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('media.assign.title', 'cs', 'Přiřadit obrázek k produktu', 'core', 0, NULL, 0),
    ('media.assign.title', 'en', 'Assign image to product', 'core', 0, NULL, 0),
    ('media.assign.description', 'cs', 'Vyber obrázek z knihovny a určete, zda patří celému produktu nebo konkrétní variantě.', 'core', 0, NULL, 0),
    ('media.assign.description', 'en', 'Choose a library image and assign it to the product or a specific variant.', 'core', 0, NULL, 0),
    ('media.assign.asset', 'cs', 'Mediální soubor', 'core', 0, NULL, 0),
    ('media.assign.asset', 'en', 'Media asset', 'core', 0, NULL, 0),
    ('media.assign.variant', 'cs', 'Cíl přiřazení', 'core', 0, NULL, 0),
    ('media.assign.variant', 'en', 'Assignment target', 'core', 0, NULL, 0),
    ('media.assign.product_level', 'cs', 'Celý produkt', 'core', 0, NULL, 0),
    ('media.assign.product_level', 'en', 'Whole product', 'core', 0, NULL, 0),
    ('media.assign.alt_text', 'cs', 'Alternativní text (ALT)', 'core', 0, NULL, 0),
    ('media.assign.alt_text', 'en', 'Alternative text (ALT)', 'core', 0, NULL, 0),
    ('media.assign.sort_order', 'cs', 'Pořadí', 'core', 0, NULL, 0),
    ('media.assign.sort_order', 'en', 'Sort order', 'core', 0, NULL, 0),
    ('media.assign.is_primary', 'cs', 'Nastavit jako hlavní obrázek', 'core', 0, NULL, 0),
    ('media.assign.is_primary', 'en', 'Set as primary image', 'core', 0, NULL, 0),
    ('media.assign.submit', 'cs', 'Přiřadit obrázek', 'core', 0, NULL, 0),
    ('media.assign.submit', 'en', 'Assign image', 'core', 0, NULL, 0),
    ('media.assign.error.required', 'cs', 'Vyber mediální soubor.', 'core', 0, NULL, 0),
    ('media.assign.error.required', 'en', 'Select a media asset.', 'core', 0, NULL, 0),
    ('media.assign.error.invalid', 'cs', 'Zadané údaje pro přiřazení nejsou platné.', 'core', 0, NULL, 0),
    ('media.assign.error.invalid', 'en', 'The submitted assignment data is invalid.', 'core', 0, NULL, 0),
    ('media.assign.success', 'cs', 'Obrázek byl přiřazen k produktu.', 'core', 0, NULL, 0),
    ('media.assign.success', 'en', 'The image was assigned to the product.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('products.images.title', 'cs', 'Obrázky produktu', 'core', 0, NULL, 0),
    ('products.images.title', 'en', 'Product images', 'core', 0, NULL, 0),
    ('products.images.description', 'cs', 'Přiřazené obrázky produktu a jeho variant.', 'core', 0, NULL, 0),
    ('products.images.description', 'en', 'Images assigned to this product and its variants.', 'core', 0, NULL, 0),
    ('products.images.manage', 'cs', 'Spravovat obrázky', 'core', 0, NULL, 0),
    ('products.images.manage', 'en', 'Manage images', 'core', 0, NULL, 0),
    ('products.images.empty', 'cs', 'Produkt zatím nemá přiřazené žádné obrázky.', 'core', 0, NULL, 0),
    ('products.images.empty', 'en', 'No images are assigned to this product yet.', 'core', 0, NULL, 0),
    ('products.images.primary', 'cs', 'Hlavní obrázek', 'core', 0, NULL, 0),
    ('products.images.primary', 'en', 'Primary image', 'core', 0, NULL, 0),
    ('products.images.product_level', 'cs', 'Produkt', 'core', 0, NULL, 0),
    ('products.images.product_level', 'en', 'Product', 'core', 0, NULL, 0),
    ('products.images.variant', 'cs', 'Varianta', 'core', 0, NULL, 0),
    ('products.images.variant', 'en', 'Variant', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE id = id;

COMMIT;
