-- ArgoMi BestShop · F5-17 · Admin product variant matrix messages (cs/en)
-- Idempotent seed; existing system and customized translations remain unchanged.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('products.variants.title', 'cs', 'Matice variant', 'core', 0, NULL, 0),
    ('products.variants.title', 'en', 'Variant matrix', 'core', 0, NULL, 0),
    ('products.variants.back_to_product', 'cs', 'Zpět na produkt', 'core', 0, NULL, 0),
    ('products.variants.back_to_product', 'en', 'Back to product', 'core', 0, NULL, 0),
    ('products.variants.axes', 'cs', 'Variantní osy', 'core', 0, NULL, 0),
    ('products.variants.axes', 'en', 'Variant axes', 'core', 0, NULL, 0),
    ('products.variants.combinations', 'cs', 'Kombinace', 'core', 0, NULL, 0),
    ('products.variants.combinations', 'en', 'Combinations', 'core', 0, NULL, 0),
    ('products.variants.generate', 'cs', 'Vytvořit vybrané kombinace', 'core', 0, NULL, 0),
    ('products.variants.generate', 'en', 'Generate selected combinations', 'core', 0, NULL, 0),
    ('products.variants.select_combinations', 'cs', 'Chybějící kombinace k vytvoření', 'core', 0, NULL, 0),
    ('products.variants.select_combinations', 'en', 'Missing combinations to generate', 'core', 0, NULL, 0),
    ('products.variants.no_axes', 'cs', 'Produkt zatím nemá přiřazené variantní osy.', 'core', 0, NULL, 0),
    ('products.variants.no_axes', 'en', 'This product has no variant axes assigned yet.', 'core', 0, NULL, 0),
    ('products.variants.no_values', 'cs', 'Některé osy zatím nemají žádné dostupné hodnoty. Pro číselné a textové osy se hodnoty odvozují z již existujících variant.', 'core', 0, NULL, 0),
    ('products.variants.no_values', 'en', 'Some axes have no available values yet. Numeric and text axis values are currently derived from existing variants.', 'core', 0, NULL, 0),
    ('products.variants.no_missing', 'cs', 'Všechny dostupné kombinace již existují.', 'core', 0, NULL, 0),
    ('products.variants.no_missing', 'en', 'All available combinations already exist.', 'core', 0, NULL, 0),
    ('products.variants.state', 'cs', 'Stav', 'core', 0, NULL, 0),
    ('products.variants.state', 'en', 'Status', 'core', 0, NULL, 0),
    ('products.variants.sku', 'cs', 'SKU', 'core', 0, NULL, 0),
    ('products.variants.sku', 'en', 'SKU', 'core', 0, NULL, 0),
    ('products.variants.existing', 'cs', 'Existující', 'core', 0, NULL, 0),
    ('products.variants.existing', 'en', 'Existing', 'core', 0, NULL, 0),
    ('products.variants.missing', 'cs', 'Chybí', 'core', 0, NULL, 0),
    ('products.variants.missing', 'en', 'Missing', 'core', 0, NULL, 0),
    ('products.variants.active', 'cs', 'Aktivní', 'core', 0, NULL, 0),
    ('products.variants.active', 'en', 'Active', 'core', 0, NULL, 0),
    ('products.variants.inactive', 'cs', 'Neaktivní', 'core', 0, NULL, 0),
    ('products.variants.inactive', 'en', 'Inactive', 'core', 0, NULL, 0),
    ('products.variants.default', 'cs', 'Výchozí', 'core', 0, NULL, 0),
    ('products.variants.default', 'en', 'Default', 'core', 0, NULL, 0),
    ('products.variants.value.true', 'cs', 'Ano', 'core', 0, NULL, 0),
    ('products.variants.value.true', 'en', 'Yes', 'core', 0, NULL, 0),
    ('products.variants.value.false', 'cs', 'Ne', 'core', 0, NULL, 0),
    ('products.variants.value.false', 'en', 'No', 'core', 0, NULL, 0),
    ('products.variants.generate.success', 'cs', 'Vybrané kombinace byly vytvořeny.', 'core', 0, NULL, 0),
    ('products.variants.generate.success', 'en', 'The selected combinations were generated.', 'core', 0, NULL, 0),
    ('products.variants.generate.unchanged', 'cs', 'Žádné nové varianty nebylo potřeba vytvořit.', 'core', 0, NULL, 0),
    ('products.variants.generate.unchanged', 'en', 'No new variants needed to be created.', 'core', 0, NULL, 0),
    ('products.variants.generate.error', 'cs', 'Kombinace se nepodařilo vytvořit. Zkontroluj výběr a zkus to znovu.', 'core', 0, NULL, 0),
    ('products.variants.generate.error', 'en', 'The combinations could not be generated. Review the selection and try again.', 'core', 0, NULL, 0),
    ('products.variants.product_not_found', 'cs', 'Produkt nebyl nalezen.', 'core', 0, NULL, 0),
    ('products.variants.product_not_found', 'en', 'The product was not found.', 'core', 0, NULL, 0),
    ('products.variants.cancel', 'cs', 'Zpět na úpravu produktu', 'core', 0, NULL, 0),
    ('products.variants.cancel', 'en', 'Back to product editor', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
