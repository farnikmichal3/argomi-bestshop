-- ArgoMi BestShop · F5-21 · Customer product options (cs/en)
-- Idempotent system messages for customer option configuration in Admin.

SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('products.customer_options.title', 'cs', 'Volby zákazníka', 'core', 0, NULL, 0),
    ('products.customer_options.title', 'en', 'Customer options', 'core', 0, NULL, 0),
    ('products.customer_options.description', 'cs', 'Nastavte volby, které zákazník vyplní nebo vybere při nákupu. Výběrové volby mají vlastní lokalizované hodnoty; textové volby přijímají text do 500 znaků. Cenové příplatky budou spravovány samostatně v modulu Pricing.', 'core', 0, NULL, 0),
    ('products.customer_options.description', 'en', 'Configure the choices or information a customer provides when buying this product. Choice options have localized values; text options accept up to 500 characters. Price surcharges are managed separately by the Pricing module.', 'core', 0, NULL, 0),
    ('products.customer_options.option.code', 'cs', 'Interní kód volby', 'core', 0, NULL, 0),
    ('products.customer_options.option.code', 'en', 'Option code', 'core', 0, NULL, 0),
    ('products.customer_options.option.type', 'cs', 'Typ volby', 'core', 0, NULL, 0),
    ('products.customer_options.option.type', 'en', 'Option type', 'core', 0, NULL, 0),
    ('products.customer_options.type.choice', 'cs', 'Výběr z hodnot', 'core', 0, NULL, 0),
    ('products.customer_options.type.choice', 'en', 'Choice list', 'core', 0, NULL, 0),
    ('products.customer_options.type.text', 'cs', 'Vlastní text', 'core', 0, NULL, 0),
    ('products.customer_options.type.text', 'en', 'Custom text', 'core', 0, NULL, 0),
    ('products.customer_options.option.required', 'cs', 'Povinná volba', 'core', 0, NULL, 0),
    ('products.customer_options.option.required', 'en', 'Required option', 'core', 0, NULL, 0),
    ('products.customer_options.option.sort_order', 'cs', 'Pořadí', 'core', 0, NULL, 0),
    ('products.customer_options.option.sort_order', 'en', 'Sort order', 'core', 0, NULL, 0),
    ('products.customer_options.option.name', 'cs', 'Název volby', 'core', 0, NULL, 0),
    ('products.customer_options.option.name', 'en', 'Option name', 'core', 0, NULL, 0),
    ('products.customer_options.option.delete', 'cs', 'Odstranit volbu', 'core', 0, NULL, 0),
    ('products.customer_options.option.delete', 'en', 'Delete option', 'core', 0, NULL, 0),
    ('products.customer_options.value.code', 'cs', 'Interní kód hodnoty', 'core', 0, NULL, 0),
    ('products.customer_options.value.code', 'en', 'Value code', 'core', 0, NULL, 0),
    ('products.customer_options.value.label', 'cs', 'Popisek hodnoty', 'core', 0, NULL, 0),
    ('products.customer_options.value.label', 'en', 'Value label', 'core', 0, NULL, 0),
    ('products.customer_options.value.sort_order', 'cs', 'Pořadí hodnoty', 'core', 0, NULL, 0),
    ('products.customer_options.value.sort_order', 'en', 'Value sort order', 'core', 0, NULL, 0),
    ('products.customer_options.value.delete', 'cs', 'Odstranit hodnotu', 'core', 0, NULL, 0),
    ('products.customer_options.value.delete', 'en', 'Delete value', 'core', 0, NULL, 0),
    ('products.customer_options.submit', 'cs', 'Uložit volby zákazníka', 'core', 0, NULL, 0),
    ('products.customer_options.submit', 'en', 'Save customer options', 'core', 0, NULL, 0),
    ('products.customer_options.saved', 'cs', 'Volby zákazníka byly uloženy.', 'core', 0, NULL, 0),
    ('products.customer_options.saved', 'en', 'Customer options have been saved.', 'core', 0, NULL, 0),
    ('products.customer_options.error.invalid', 'cs', 'Konfigurace voleb není platná. Zkontrolujte kódy, překlady a hodnoty; volbu nebo hodnotu použitou v košíku nelze odstranit.', 'core', 0, NULL, 0),
    ('products.customer_options.error.invalid', 'en', 'The option configuration is invalid. Check codes, translations and values; options or values used in a cart cannot be deleted.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE id = id;

COMMIT;
