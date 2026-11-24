-- ArgoMi BestShop · F5-20 · Product attribute configuration editor (cs/en)
-- Idempotent system messages for product attribute values and ordered variant axes.

SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('products.configuration.title', 'cs', 'Vlastnosti a osy variant', 'core', 0, NULL, 0),
    ('products.configuration.title', 'en', 'Attributes and variant axes', 'core', 0, NULL, 0),
    ('products.configuration.description', 'cs', 'Nastav vlastnosti produktu a určete, které z nich tvoří varianty. Pořadí os určuje pořadí kombinací.', 'core', 0, NULL, 0),
    ('products.configuration.description', 'en', 'Set product properties and choose which ones define variants. Axis order determines combination order.', 'core', 0, NULL, 0),
    ('products.configuration.value.prompt', 'cs', 'Bez přiřazené hodnoty', 'core', 0, NULL, 0),
    ('products.configuration.value.prompt', 'en', 'No value assigned', 'core', 0, NULL, 0),
    ('products.configuration.boolean.yes', 'cs', 'Ano', 'core', 0, NULL, 0),
    ('products.configuration.boolean.yes', 'en', 'Yes', 'core', 0, NULL, 0),
    ('products.configuration.boolean.no', 'cs', 'Ne', 'core', 0, NULL, 0),
    ('products.configuration.boolean.no', 'en', 'No', 'core', 0, NULL, 0),
    ('products.configuration.axis', 'cs', 'Osa varianty', 'core', 0, NULL, 0),
    ('products.configuration.axis', 'en', 'Variant axis', 'core', 0, NULL, 0),
    ('products.configuration.axis_order', 'cs', 'Pořadí osy', 'core', 0, NULL, 0),
    ('products.configuration.axis_order', 'en', 'Axis order', 'core', 0, NULL, 0),
    ('products.configuration.submit', 'cs', 'Uložit vlastnosti a osy', 'core', 0, NULL, 0),
    ('products.configuration.submit', 'en', 'Save attributes and axes', 'core', 0, NULL, 0),
    ('products.configuration.saved', 'cs', 'Vlastnosti produktu a osy variant byly uloženy.', 'core', 0, NULL, 0),
    ('products.configuration.saved', 'en', 'Product attributes and variant axes were saved.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE id = id;

COMMIT;
