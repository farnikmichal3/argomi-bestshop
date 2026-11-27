-- ArgoMi BestShop · F5-23 · Admin product relation management messages
-- Catalog product relation editor; never overwrite customized translations.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('products.relations.title', 'cs', 'Související produkty', 'core', 0, NULL, 0),
    ('products.relations.title', 'en', 'Related products', 'core', 0, NULL, 0),
    ('products.relations.description', 'cs', 'Spravujte doplňkové, alternativní a kompatibilní produkty. Vztahy jsou směrové a jejich pořadí ovlivňuje zobrazení v katalogu.', 'core', 0, NULL, 0),
    ('products.relations.description', 'en', 'Manage accessory, alternative and compatible products. Relations are directional and their order controls catalog display.', 'core', 0, NULL, 0),
    ('products.relations.empty', 'cs', 'K produktu zatím nejsou přiřazeny žádné související produkty.', 'core', 0, NULL, 0),
    ('products.relations.empty', 'en', 'No related products have been assigned yet.', 'core', 0, NULL, 0),
    ('products.relations.list.product', 'cs', 'Produkt', 'core', 0, NULL, 0),
    ('products.relations.list.product', 'en', 'Product', 'core', 0, NULL, 0),
    ('products.relations.list.type', 'cs', 'Typ vztahu', 'core', 0, NULL, 0),
    ('products.relations.list.type', 'en', 'Relation type', 'core', 0, NULL, 0),
    ('products.relations.list.position', 'cs', 'Pořadí', 'core', 0, NULL, 0),
    ('products.relations.list.position', 'en', 'Position', 'core', 0, NULL, 0),
    ('products.relations.type.accessory', 'cs', 'Doplněk', 'core', 0, NULL, 0),
    ('products.relations.type.accessory', 'en', 'Accessory', 'core', 0, NULL, 0),
    ('products.relations.type.alternative', 'cs', 'Alternativa', 'core', 0, NULL, 0),
    ('products.relations.type.alternative', 'en', 'Alternative', 'core', 0, NULL, 0),
    ('products.relations.type.compatible', 'cs', 'Kompatibilní produkt', 'core', 0, NULL, 0),
    ('products.relations.type.compatible', 'en', 'Compatible product', 'core', 0, NULL, 0),
    ('products.relations.add.title', 'cs', 'Přidat související produkt', 'core', 0, NULL, 0),
    ('products.relations.add.title', 'en', 'Add related product', 'core', 0, NULL, 0),
    ('products.relations.add.product', 'cs', 'Produkt', 'core', 0, NULL, 0),
    ('products.relations.add.product', 'en', 'Product', 'core', 0, NULL, 0),
    ('products.relations.add.product_prompt', 'cs', 'Vyberte aktivní produkt', 'core', 0, NULL, 0),
    ('products.relations.add.product_prompt', 'en', 'Select an active product', 'core', 0, NULL, 0),
    ('products.relations.add.type', 'cs', 'Typ vztahu', 'core', 0, NULL, 0),
    ('products.relations.add.type', 'en', 'Relation type', 'core', 0, NULL, 0),
    ('products.relations.add.submit', 'cs', 'Přidat vztah', 'core', 0, NULL, 0),
    ('products.relations.add.submit', 'en', 'Add relation', 'core', 0, NULL, 0),
    ('products.relations.add.no_candidates', 'cs', 'Nejsou dostupné žádné další aktivní produkty.', 'core', 0, NULL, 0),
    ('products.relations.add.no_candidates', 'en', 'No other active products are available.', 'core', 0, NULL, 0),
    ('products.relations.remove.title', 'cs', 'Odebrat vztah', 'core', 0, NULL, 0),
    ('products.relations.remove.title', 'en', 'Remove relation', 'core', 0, NULL, 0),
    ('products.relations.remove.relation', 'cs', 'Související produkt', 'core', 0, NULL, 0),
    ('products.relations.remove.relation', 'en', 'Related product', 'core', 0, NULL, 0),
    ('products.relations.remove.prompt', 'cs', 'Vyberte vztah k odebrání', 'core', 0, NULL, 0),
    ('products.relations.remove.prompt', 'en', 'Select a relation to remove', 'core', 0, NULL, 0),
    ('products.relations.remove.submit', 'cs', 'Odebrat', 'core', 0, NULL, 0),
    ('products.relations.remove.submit', 'en', 'Remove', 'core', 0, NULL, 0),
    ('products.relations.order.title', 'cs', 'Pořadí zobrazení', 'core', 0, NULL, 0),
    ('products.relations.order.title', 'en', 'Display order', 'core', 0, NULL, 0),
    ('products.relations.order.position', 'cs', 'Pořadí', 'core', 0, NULL, 0),
    ('products.relations.order.position', 'en', 'Order', 'core', 0, NULL, 0),
    ('products.relations.order.submit', 'cs', 'Uložit pořadí', 'core', 0, NULL, 0),
    ('products.relations.order.submit', 'en', 'Save order', 'core', 0, NULL, 0),
    ('products.relations.added', 'cs', 'Související produkt byl přidán.', 'core', 0, NULL, 0),
    ('products.relations.added', 'en', 'Related product added.', 'core', 0, NULL, 0),
    ('products.relations.removed', 'cs', 'Vztah produktu byl odebrán.', 'core', 0, NULL, 0),
    ('products.relations.removed', 'en', 'Product relation removed.', 'core', 0, NULL, 0),
    ('products.relations.order.saved', 'cs', 'Pořadí souvisejících produktů bylo uloženo.', 'core', 0, NULL, 0),
    ('products.relations.order.saved', 'en', 'Related product order saved.', 'core', 0, NULL, 0),
    ('products.relations.error.required', 'cs', 'Vyplňte povinnou hodnotu.', 'core', 0, NULL, 0),
    ('products.relations.error.required', 'en', 'This field is required.', 'core', 0, NULL, 0),
    ('products.relations.error.invalid', 'cs', 'Odeslané údaje nejsou platné.', 'core', 0, NULL, 0),
    ('products.relations.error.invalid', 'en', 'The submitted data is invalid.', 'core', 0, NULL, 0),
    ('products.relations.error.duplicate_or_invalid', 'cs', 'Vztah již existuje nebo produkt není dostupný.', 'core', 0, NULL, 0),
    ('products.relations.error.duplicate_or_invalid', 'en', 'The relation already exists or the product is unavailable.', 'core', 0, NULL, 0),
    ('products.relations.error.position', 'cs', 'Každá položka musí mít jedinečné nezáporné pořadí.', 'core', 0, NULL, 0),
    ('products.relations.error.position', 'en', 'Every item must have a unique non-negative position.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
