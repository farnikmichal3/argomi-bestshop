-- ArgoMi BestShop · F5-15 · Admin product listing messages (cs/en)
-- Idempotent seed; existing system and customized translations remain unchanged.

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO catalog_message_translation
    (message_key, locale, message_text, source, is_customized, default_hash, is_outdated)
VALUES
    ('products.admin.title', 'cs', 'Produkty', 'core', 0, NULL, 0),
    ('products.admin.title', 'en', 'Products', 'core', 0, NULL, 0),
    ('products.admin.results', 'cs', 'Počet produktů', 'core', 0, NULL, 0),
    ('products.admin.results', 'en', 'Products found', 'core', 0, NULL, 0),
    ('products.admin.filters.title', 'cs', 'Filtry produktů', 'core', 0, NULL, 0),
    ('products.admin.filters.title', 'en', 'Product filters', 'core', 0, NULL, 0),
    ('products.admin.filter.category', 'cs', 'Kategorie', 'core', 0, NULL, 0),
    ('products.admin.filter.category', 'en', 'Category', 'core', 0, NULL, 0),
    ('products.admin.filter.status', 'cs', 'Stav', 'core', 0, NULL, 0),
    ('products.admin.filter.status', 'en', 'Status', 'core', 0, NULL, 0),
    ('products.admin.filter.type', 'cs', 'Typ produktu', 'core', 0, NULL, 0),
    ('products.admin.filter.type', 'en', 'Product type', 'core', 0, NULL, 0),
    ('products.admin.filter.all', 'cs', 'Vše', 'core', 0, NULL, 0),
    ('products.admin.filter.all', 'en', 'All', 'core', 0, NULL, 0),
    ('products.admin.filter.apply', 'cs', 'Filtrovat', 'core', 0, NULL, 0),
    ('products.admin.filter.apply', 'en', 'Apply filters', 'core', 0, NULL, 0),
    ('products.admin.column.id', 'cs', 'ID', 'core', 0, NULL, 0),
    ('products.admin.column.id', 'en', 'ID', 'core', 0, NULL, 0),
    ('products.admin.column.code', 'cs', 'Kód', 'core', 0, NULL, 0),
    ('products.admin.column.code', 'en', 'Code', 'core', 0, NULL, 0),
    ('products.admin.column.name', 'cs', 'Název', 'core', 0, NULL, 0),
    ('products.admin.column.name', 'en', 'Name', 'core', 0, NULL, 0),
    ('products.admin.column.type', 'cs', 'Typ', 'core', 0, NULL, 0),
    ('products.admin.column.type', 'en', 'Type', 'core', 0, NULL, 0),
    ('products.admin.column.status', 'cs', 'Stav', 'core', 0, NULL, 0),
    ('products.admin.column.status', 'en', 'Status', 'core', 0, NULL, 0),
    ('products.admin.column.categories', 'cs', 'Kategorie', 'core', 0, NULL, 0),
    ('products.admin.column.categories', 'en', 'Categories', 'core', 0, NULL, 0),
    ('products.admin.empty', 'cs', 'Žádné produkty neodpovídají zvoleným filtrům.', 'core', 0, NULL, 0),
    ('products.admin.empty', 'en', 'No products match the selected filters.', 'core', 0, NULL, 0),
    ('products.admin.type.physical', 'cs', 'Fyzický', 'core', 0, NULL, 0),
    ('products.admin.type.physical', 'en', 'Physical', 'core', 0, NULL, 0),
    ('products.admin.type.digital', 'cs', 'Digitální', 'core', 0, NULL, 0),
    ('products.admin.type.digital', 'en', 'Digital', 'core', 0, NULL, 0),
    ('products.admin.type.service', 'cs', 'Služba', 'core', 0, NULL, 0),
    ('products.admin.type.service', 'en', 'Service', 'core', 0, NULL, 0),
    ('products.admin.status.draft', 'cs', 'Koncept', 'core', 0, NULL, 0),
    ('products.admin.status.draft', 'en', 'Draft', 'core', 0, NULL, 0),
    ('products.admin.status.active', 'cs', 'Aktivní', 'core', 0, NULL, 0),
    ('products.admin.status.active', 'en', 'Active', 'core', 0, NULL, 0),
    ('products.admin.status.archived', 'cs', 'Archivovaný', 'core', 0, NULL, 0),
    ('products.admin.status.archived', 'en', 'Archived', 'core', 0, NULL, 0),
    ('products.admin.pagination', 'cs', 'Stránkování produktů', 'core', 0, NULL, 0),
    ('products.admin.pagination', 'en', 'Product pagination', 'core', 0, NULL, 0),
    ('products.admin.pagination.page', 'cs', 'Stránka', 'core', 0, NULL, 0),
    ('products.admin.pagination.page', 'en', 'Page', 'core', 0, NULL, 0),
    ('products.admin.pagination.previous', 'cs', 'Předchozí', 'core', 0, NULL, 0),
    ('products.admin.pagination.previous', 'en', 'Previous', 'core', 0, NULL, 0),
    ('products.admin.pagination.next', 'cs', 'Další', 'core', 0, NULL, 0),
    ('products.admin.pagination.next', 'en', 'Next', 'core', 0, NULL, 0)
ON DUPLICATE KEY UPDATE
    id = id;

COMMIT;
