-- ArgoMi BestShop · Catalog administration category overview messages (cs/en)
-- MySQL 8.0.16+ · InnoDB
-- Idempotentní seed; existující systémové i vlastní překlady zůstávají beze změny.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('categories.title', 'cs', 'Kategorie katalogu', 'core', 0, NULL, 0),
    ('categories.title', 'en', 'Catalog categories', 'core', 0, NULL, 0),
    ('categories.column.id', 'cs', 'ID', 'core', 0, NULL, 0),
    ('categories.column.id', 'en', 'ID', 'core', 0, NULL, 0),
    ('categories.column.code', 'cs', 'Kód', 'core', 0, NULL, 0),
    ('categories.column.code', 'en', 'Code', 'core', 0, NULL, 0),
    ('categories.column.name', 'cs', 'Název', 'core', 0, NULL, 0),
    ('categories.column.name', 'en', 'Name', 'core', 0, NULL, 0),
    ('categories.column.status', 'cs', 'Stav', 'core', 0, NULL, 0),
    ('categories.column.status', 'en', 'Status', 'core', 0, NULL, 0),
    ('categories.status.active', 'cs', 'Aktivní', 'core', 0, NULL, 0),
    ('categories.status.active', 'en', 'Active', 'core', 0, NULL, 0),
    ('categories.status.inactive', 'cs', 'Neaktivní', 'core', 0, NULL, 0),
    ('categories.status.inactive', 'en', 'Inactive', 'core', 0, NULL, 0),
    ('categories.empty', 'cs', 'Zatím nejsou vytvořené žádné kategorie.', 'core', 0, NULL, 0),
    ('categories.empty', 'en', 'No categories have been created yet.', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
