SET NAMES utf8mb4;

INSERT INTO language (id, code, name, is_default, is_active, sort_order) VALUES
    (1, 'cs', 'Čeština', 1, 1, 1),
    (2, 'en', 'English', 0, 1, 2);

INSERT INTO currency (id, code, name, symbol, minor_units, is_default, is_active, sort_order) VALUES
    (1, 'CZK', 'Czech koruna', 'Kč', 2, 1, 1, 1),
    (2, 'EUR', 'Euro',         '€',  2, 0, 1, 2);

INSERT INTO unit (id, code, is_active, sort_order) VALUES
    (1, 'pcs', 1, 1),
    (2, 'g',   1, 2),
    (3, 'kg',  1, 3),
    (4, 'm',   1, 4),
    (5, 'm2',  1, 5),
    (6, 'W',   1, 6);

INSERT INTO unit_translation (unit_id, locale, symbol, name) VALUES
    (1, 'cs', 'ks', '{count, plural, one {kus} few {kusy} many {kusu} other {kusů}}'),
    (1, 'en', 'pcs', '{count, plural, one {piece} other {pieces}}'),
    (2, 'cs', 'g',  '{count, plural, one {gram} few {gramy} many {gramu} other {gramů}}'),
    (2, 'en', 'g',  '{count, plural, one {gram} other {grams}}'),
    (3, 'cs', 'kg', '{count, plural, one {kilogram} few {kilogramy} many {kilogramu} other {kilogramů}}'),
    (3, 'en', 'kg', '{count, plural, one {kilogram} other {kilograms}}'),
    (4, 'cs', 'm',  '{count, plural, one {metr} few {metry} many {metru} other {metrů}}'),
    (4, 'en', 'm',  '{count, plural, one {meter} other {meters}}'),
    (5, 'cs', 'm²', '{count, plural, one {metr čtvereční} few {metry čtvereční} many {metru čtverečního} other {metrů čtverečních}}'),
    (5, 'en', 'm²', '{count, plural, one {square meter} other {square meters}}'),
    (6, 'cs', 'W',  '{count, plural, one {watt} few {watty} many {wattu} other {wattů}}'),
    (6, 'en', 'W',  '{count, plural, one {watt} other {watts}}');

INSERT INTO tax_class (id, code, name) VALUES
    (1, 'standard', 'Základní sazba'),
    (2, 'reduced',  'Snížená sazba');

INSERT INTO tax_rate (id, tax_class_id, country_code, rate_bp, valid_from, valid_to) VALUES
    (1, 1, 'CZ', 2100, '2024-01-01', NULL),
    (2, 2, 'CZ', 1200, '2024-01-01', NULL);
