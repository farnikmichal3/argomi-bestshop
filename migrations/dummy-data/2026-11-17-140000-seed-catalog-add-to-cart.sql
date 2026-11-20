-- ArgoMi BestShop · F4-13 · Catalog: add-to-cart system message
-- MySQL 8.0.16+ · InnoDB
--
-- Seed pouze doplňuje chybějící jazykové řádky. Konflikt unikátního
-- (locale, message_key) záměrně ponechá celý existující řádek beze změny.
-- default_hash zůstává NULL; jeho správa patří do F4-14.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized,
     default_hash, is_outdated)
VALUES
    ('button.add_to_cart', 'cs', 'Přidat do košíku', 'core', 0, NULL, 0),
    ('button.add_to_cart', 'en', 'Add to cart', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
