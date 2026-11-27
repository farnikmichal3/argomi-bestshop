-- ArgoMi BestShop · F5-25 · Product copy messages (cs/en)
-- Idempotent seed; existing system and customized translations remain unchanged.

SET NAMES utf8mb4;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('products.lifecycle.copy.confirm', 'cs', 'Vytvořit nový produkt z archivovaného', 'core', 0, NULL, 0),
    ('products.lifecycle.copy.confirm', 'en', 'Create a new product from this archived product', 'core', 0, NULL, 0),
    ('products.lifecycle.copy.description', 'cs', 'Vznikne nový koncept s novým ID a novými variantami. Přenesou se katalogové údaje a aktuální ceny, nikoli EAN, skladové stavy, historie ani objednávky.', 'core', 0, NULL, 0),
    ('products.lifecycle.copy.description', 'en', 'A new draft with new IDs and variants will be created. Catalog details and current prices are copied, but not EANs, stock quantities, history, or orders.', 'core', 0, NULL, 0),
    ('products.lifecycle.copy.success', 'cs', 'Nový koncept produktu byl vytvořen. Doplň nové identifikátory a zkontroluj ceny i varianty.', 'core', 0, NULL, 0),
    ('products.lifecycle.copy.success', 'en', 'A new product draft was created. Add new identifiers and review its prices and variants.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;
