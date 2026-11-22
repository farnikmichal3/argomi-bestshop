-- ArgoMi BestShop · F5-38 · Shared admin layout and navigation labels
-- MySQL 8.0.16+ · InnoDB · utf8mb4
-- Idempotentní výchozí překlady navigace a administračního rozhraní.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO admin_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('admin.layout.skip', 'cs', 'Přeskočit na obsah', 'core', 0, NULL, 0),
    ('admin.layout.skip', 'en', 'Skip to content', 'core', 0, NULL, 0),
    ('admin.navigation.label', 'cs', 'Hlavní navigace administrace', 'core', 0, NULL, 0),
    ('admin.navigation.label', 'en', 'Main administration navigation', 'core', 0, NULL, 0),
    ('admin.navigation.toggle', 'cs', 'Přepnout navigaci', 'core', 0, NULL, 0),
    ('admin.navigation.toggle', 'en', 'Toggle navigation', 'core', 0, NULL, 0),
    ('admin.navigation.dashboard', 'cs', 'Přehled', 'core', 0, NULL, 0),
    ('admin.navigation.dashboard', 'en', 'Dashboard', 'core', 0, NULL, 0),
    ('admin.navigation.products', 'cs', 'Produkty', 'core', 0, NULL, 0),
    ('admin.navigation.products', 'en', 'Products', 'core', 0, NULL, 0),
    ('admin.navigation.categories', 'cs', 'Kategorie', 'core', 0, NULL, 0),
    ('admin.navigation.categories', 'en', 'Categories', 'core', 0, NULL, 0),
    ('admin.navigation.translations', 'cs', 'Překlady', 'core', 0, NULL, 0),
    ('admin.navigation.translations', 'en', 'Translations', 'core', 0, NULL, 0),
    ('admin.dashboard.title', 'cs', 'Přehled administrace', 'core', 0, NULL, 0),
    ('admin.dashboard.title', 'en', 'Administration dashboard', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
