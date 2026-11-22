-- ArgoMi BestShop · F5-38 · AdminLTE navigation labels
-- MySQL 8.0.16+ · InnoDB · utf8mb4
-- Idempotentní popisky sekcí administračního postranního menu.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO admin_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('admin.navigation.main', 'cs', 'Hlavní nabídka', 'core', 0, NULL, 0),
    ('admin.navigation.main', 'en', 'Main menu', 'core', 0, NULL, 0),
    ('admin.navigation.catalog', 'cs', 'Katalog', 'core', 0, NULL, 0),
    ('admin.navigation.catalog', 'en', 'Catalog', 'core', 0, NULL, 0),
    ('admin.navigation.system', 'cs', 'Systém', 'core', 0, NULL, 0),
    ('admin.navigation.system', 'en', 'System', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
