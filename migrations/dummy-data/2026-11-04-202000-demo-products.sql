SET NAMES utf8mb4;

INSERT INTO category (id, parent_id, code, sort_order, is_active) VALUES
    (1, NULL, 'clothing',    1, 1),
    (2, NULL, 'electronics', 2, 1),
    (3, 2,    'cables',      1, 1),
    (4, 2,    'chargers',    2, 1);

INSERT INTO category_translation (category_id, locale, name, slug, description) VALUES
    (1, 'cs', 'Oblečení',   'obleceni',   NULL),
    (1, 'en', 'Clothing',   'clothing',   NULL),
    (2, 'cs', 'Elektronika','elektronika',NULL),
    (2, 'en', 'Electronics','electronics',NULL),
    (3, 'cs', 'Kabely',     'kabely',     NULL),
    (3, 'en', 'Cables',     'cables',     NULL),
    (4, 'cs', 'Nabíječky',  'nabijecky',  NULL),
    (4, 'en', 'Chargers',   'chargers',   NULL);

INSERT INTO attribute (id, code, type, unit_id, is_filterable, sort_order) VALUES
    (1, 'color',        'enum',   NULL, 1, 1),
    (2, 'size',         'enum',   NULL, 1, 2),
    (3, 'length_m',     'number', 4,    1, 3),
    (4, 'c_connectors', 'number', NULL, 1, 4),
    (5, 'slots',        'number', NULL, 1, 5),
    (6, 'power_w',      'number', 6,    1, 6),
    (7, 'gan',          'bool',   NULL, 1, 7);

INSERT INTO attribute_translation (attribute_id, locale, name) VALUES
    (1, 'cs', 'Barva'),                         (1, 'en', 'Color'),
    (2, 'cs', 'Velikost'),                      (2, 'en', 'Size'),
    (3, 'cs', 'Délka'),                         (3, 'en', 'Length'),
    (4, 'cs', 'Počet konektorů USB-C'),         (4, 'en', 'Number of USB-C connectors'),
    (5, 'cs', 'Počet portů'),                   (5, 'en', 'Number of ports'),
    (6, 'cs', 'Výkon'),                         (6, 'en', 'Power'),
    (7, 'cs', 'Technologie GaN'),               (7, 'en', 'GaN technology');

INSERT INTO attribute_value (id, attribute_id, code, sort_order, meta) VALUES
    (1, 1, 'black', 1, '{"hex": "#111111"}'),
    (2, 1, 'white', 2, '{"hex": "#ffffff"}'),
    (3, 2, 's',     1, NULL),
    (4, 2, 'm',     2, NULL),
    (5, 2, 'l',     3, NULL);

INSERT INTO attribute_value_translation (attribute_value_id, locale, label) VALUES
    (1, 'cs', 'Černá'), (1, 'en', 'Black'),
    (2, 'cs', 'Bílá'),  (2, 'en', 'White'),
    (3, 'cs', 'S'),     (3, 'en', 'S'),
    (4, 'cs', 'M'),     (4, 'en', 'M'),
    (5, 'cs', 'L'),     (5, 'en', 'L');

INSERT INTO product (id, code, status, tax_class_id) VALUES
    (1, 'tshirt-printed', 'active', 1),
    (2, 'usb-c-cable',    'active', 1),
    (3, 'gan-charger',    'active', 1);

INSERT INTO product_translation
    (product_id, locale, name, slug, short_description, description, meta_title, meta_description) VALUES
    (1, 'cs', 'Tričko s potiskem', 'tricko-s-potiskem', 'Bavlněné tričko.', '<p>Pohodlné tričko.</p>', 'Tričko', 'Popis'),
    (1, 'en', 'Printed T-shirt', 'printed-t-shirt', 'Cotton T-shirt.', '<p>Comfortable t-shirt.</p>', 'T-shirt', 'Desc'),
    (2, 'cs', 'USB-C kabel', 'usb-c-kabel', 'Odolný kabel.', '<p>Kabel.</p>', 'Kabel', 'Popis'),
    (2, 'en', 'USB-C cable', 'usb-c-cable', 'Durable cable.', '<p>Cable.</p>', 'Cable', 'Desc'),
    (3, 'cs', 'GaN nabíječka', 'gan-nabijecka', 'Nabíječka GaN.', '<p>GaN.</p>', 'Nabíječka', 'Popis'),
    (3, 'en', 'GaN charger', 'gan-charger', 'GaN charger.', '<p>GaN.</p>', 'Charger', 'Desc');

INSERT INTO product_category (product_id, category_id, sort_order) VALUES
    (1, 1, 0), (2, 3, 0), (3, 4, 0);

INSERT INTO product_variant_axis (product_id, attribute_id, sort_order) VALUES
    (1, 1, 1), (1, 2, 2), (2, 3, 1), (2, 4, 2), (2, 1, 3), (3, 5, 1), (3, 6, 2), (3, 1, 3);

INSERT INTO product_attribute_value (product_id, attribute_id, attribute_value_id, value_number, value_text) VALUES
    (3, 7, NULL, 1, NULL);

INSERT INTO product_variant (id, product_id, sku, combination_hash, is_default, is_active, weight_g, sort_order) VALUES
    ( 1, 1, 'TSHIRT-BK-S',     SHA2('color=black;size=s', 256), 1, 1, 180, 1),
    ( 2, 1, 'TSHIRT-BK-M',     SHA2('color=black;size=m', 256), 0, 1, 190, 2),
    ( 3, 1, 'TSHIRT-BK-L',     SHA2('color=black;size=l', 256), 0, 1, 200, 3),
    ( 4, 1, 'TSHIRT-WH-S',     SHA2('color=white;size=s', 256), 0, 1, 180, 4),
    ( 5, 1, 'TSHIRT-WH-M',     SHA2('color=white;size=m', 256), 0, 1, 190, 5),
    ( 6, 1, 'TSHIRT-WH-L',     SHA2('color=white;size=l', 256), 0, 1, 200, 6),
    ( 7, 2, 'CABLE-CC-1M-BK',  SHA2('c_connectors=2;color=black;length_m=1', 256), 1, 1, 30, 1),
    ( 8, 2, 'CABLE-CC-2M-BK',  SHA2('c_connectors=2;color=black;length_m=2', 256), 0, 1, 45, 2),
    ( 9, 2, 'CABLE-CC-2M-WH',  SHA2('c_connectors=2;color=white;length_m=2', 256), 0, 1, 45, 3),
    (10, 2, 'CABLE-CA-1M-BK',  SHA2('c_connectors=1;color=black;length_m=1', 256), 0, 1, 30, 4),
    (11, 2, 'CABLE-CA-2M-BK',  SHA2('c_connectors=1;color=black;length_m=2', 256), 0, 1, 45, 5),
    (12, 3, 'GAN-1X65-WH',     SHA2('color=white;power_w=65;slots=1', 256), 1, 1, 90, 1),
    (13, 3, 'GAN-2X65-BK',     SHA2('color=black;power_w=65;slots=2', 256), 0, 1, 120, 2),
    (14, 3, 'GAN-2X100-BK',    SHA2('color=black;power_w=100;slots=2', 256), 0, 1, 180, 3),
    (15, 3, 'GAN-3X100-BK',    SHA2('color=black;power_w=100;slots=3', 256), 0, 1, 220, 4);

INSERT INTO product_variant_attribute_value
    (product_variant_id, product_id, attribute_id, attribute_value_id, value_number, value_text) VALUES
    ( 1, 1, 1, 1, NULL, NULL), ( 1, 1, 2, 3, NULL, NULL),
    ( 2, 1, 1, 1, NULL, NULL), ( 2, 1, 2, 4, NULL, NULL),
    ( 3, 1, 1, 1, NULL, NULL), ( 3, 1, 2, 5, NULL, NULL),
    ( 4, 1, 1, 2, NULL, NULL), ( 4, 1, 2, 3, NULL, NULL),
    ( 5, 1, 1, 2, NULL, NULL), ( 5, 1, 2, 4, NULL, NULL),
    ( 6, 1, 1, 2, NULL, NULL), ( 6, 1, 2, 5, NULL, NULL),
    ( 7, 2, 3, NULL, 1, NULL), ( 7, 2, 4, NULL, 2, NULL), ( 7, 2, 1, 1, NULL, NULL),
    ( 8, 2, 3, NULL, 2, NULL), ( 8, 2, 4, NULL, 2, NULL), ( 8, 2, 1, 1, NULL, NULL),
    ( 9, 2, 3, NULL, 2, NULL), ( 9, 2, 4, NULL, 2, NULL), ( 9, 2, 1, 2, NULL, NULL),
    (10, 2, 3, NULL, 1, NULL), (10, 2, 4, NULL, 1, NULL), (10, 2, 1, 1, NULL, NULL),
    (11, 2, 3, NULL, 2, NULL), (11, 2, 4, NULL, 1, NULL), (11, 2, 1, 1, NULL, NULL),
    (12, 3, 5, NULL, 1, NULL), (12, 3, 6, NULL, 65, NULL), (12, 3, 1, 2, NULL, NULL),
    (13, 3, 5, NULL, 2, NULL), (13, 3, 6, NULL, 65, NULL), (13, 3, 1, 1, NULL, NULL),
    (14, 3, 5, NULL, 2, NULL), (14, 3, 6, NULL, 100, NULL), (14, 3, 1, 1, NULL, NULL),
    (15, 3, 5, NULL, 3, NULL), (15, 3, 6, NULL, 100, NULL), (15, 3, 1, 1, NULL, NULL);

INSERT INTO product_option (id, product_id, code, type, is_required, sort_order) VALUES
    (1, 1, 'print',      'choice', 1, 1),
    (2, 1, 'print_text', 'text',   0, 2);

INSERT INTO product_option_translation (product_option_id, locale, name) VALUES
    (1, 'cs', 'Potisk'), (1, 'en', 'Print'), (2, 'cs', 'Text potisku'), (2, 'en', 'Print text');

INSERT INTO product_option_value (id, product_option_id, code, sort_order) VALUES
    (1, 1, 'none', 1), (2, 1, 'custom', 2);

INSERT INTO product_option_value_translation (product_option_value_id, locale, label) VALUES
    (1, 'cs', 'Bez potisku'), (1, 'en', 'No print'), (2, 'cs', 'Vlastní potisk'), (2, 'en', 'Custom print');

INSERT INTO option_value_price (product_option_value_id, currency, delta_minor) VALUES
    (1, 'CZK', 0), (1, 'EUR', 0), (2, 'CZK', 12000), (2, 'EUR', 490);

INSERT INTO product_relation (product_id, related_product_id, type, sort_order) VALUES
    (3, 2, 'accessory', 1), (2, 3, 'accessory', 1);

INSERT INTO variant_price (product_variant_id, currency, amount_minor, tax_included) VALUES
    (1, 'CZK', 39900, 1), (1, 'EUR', 1690, 1),
    (2, 'CZK', 39900, 1), (2, 'EUR', 1690, 1),
    (3, 'CZK', 39900, 1), (3, 'EUR', 1690, 1),
    (4, 'CZK', 39900, 1), (4, 'EUR', 1690, 1),
    (5, 'CZK', 39900, 1), (5, 'EUR', 1690, 1),
    (6, 'CZK', 39900, 1), (6, 'EUR', 1690, 1),
    (7, 'CZK', 14900, 1), (7, 'EUR', 599, 1),
    (8, 'CZK', 19900, 1), (8, 'EUR', 799, 1),
    (9, 'CZK', 19900, 1), (9, 'EUR', 799, 1),
    (10, 'CZK', 9900, 1), (10, 'EUR', 399, 1),
    (11, 'CZK', 12900, 1), (11, 'EUR', 499, 1),
    (12, 'CZK', 69900, 1), (12, 'EUR', 2790, 1),
    (13, 'CZK', 89900, 1), (13, 'EUR', 3590, 1),
    (14, 'CZK', 119900, 1), (14, 'EUR', 4790, 1),
    (15, 'CZK', 149900, 1), (15, 'EUR', 5990, 1);

-- Počáteční ceny mají od začátku odpovídající záznam v cenové historii.
INSERT INTO variant_price_history
    (product_variant_id, currency, amount_minor, tax_included, effective_at, change_source, note)
SELECT
    product_variant_id,
    currency,
    amount_minor,
    tax_included,
    '2026-11-01 00:00:00.000000',
    'initial',
    'Počáteční demo cena'
FROM variant_price;

INSERT INTO stock_item (product_variant_id, track_stock, allow_backorder, quantity_on_hand, quantity_reserved) VALUES
    (1, 1, 0, 25, 0), (2, 1, 0, 40, 0), (3, 1, 0, 30, 0), (4, 1, 0, 20, 0), (5, 1, 0, 0, 0),
    (6, 1, 0, 15, 0), (7, 1, 0, 100, 0), (8, 1, 0, 80, 0), (9, 1, 0, 60, 0), (10, 1, 0, 120, 0),
    (11, 1, 0, 90, 0), (12, 1, 0, 35, 0), (13, 1, 0, 50, 0), (14, 1, 1, 0, 0), (15, 1, 0, 12, 0);

INSERT INTO shipping_method (id, code, tax_class_id, is_active, sort_order) VALUES
    (1, 'personal_pickup', 1, 1, 1), (2, 'pickup_point', 1, 1, 2), (3, 'courier', 1, 1, 3);

INSERT INTO shipping_method_translation (shipping_method_id, locale, name) VALUES
    (1, 'cs', 'Osobní odběr'), (1, 'en', 'Personal pickup'),
    (2, 'cs', 'Výdejní místo'), (2, 'en', 'Pickup point'),
    (3, 'cs', 'Kurýr'), (3, 'en', 'Courier');

INSERT INTO shipping_method_price (shipping_method_id, currency, amount_minor, tax_included, free_from_minor) VALUES
    (1, 'CZK', 0, 1, NULL), (1, 'EUR', 0, 1, NULL),
    (2, 'CZK', 6900, 1, 150000), (2, 'EUR', 290, 1, 6000),
    (3, 'CZK', 11900, 1, 250000), (3, 'EUR', 490, 1, 10000);

INSERT INTO payment_method (id, code, is_active, sort_order) VALUES
    (1, 'card', 1, 1), (2, 'bank_transfer', 1, 2), (3, 'cod', 1, 3);

INSERT INTO payment_method_translation (payment_method_id, locale, name) VALUES
    (1, 'cs', 'Platba kartou'), (1, 'en', 'Card payment'),
    (2, 'cs', 'Bankovní převod'), (2, 'en', 'Bank transfer'),
    (3, 'cs', 'Dobírka'), (3, 'en', 'Cash on delivery');

INSERT INTO customer (id, public_id, email, password_hash, first_name, last_name, locale) VALUES
    (1, '01J9ZK3Q8M5N7V2R4T6X8B0C1D', 'jana.novakova@example.com', NULL, 'Jana', 'Nováková', 'cs'),
    (2, '01J9ZK3Q8M5N7V2R4T6X8B0C2E', 'john.smith@example.com', NULL, 'John', 'Smith', 'en');

INSERT INTO customer_address (customer_id, type, name, company, street, city, postal_code, country_code, is_default) VALUES
    (1, 'billing', 'Jana Nováková', NULL, 'Příkladná 12', 'Ostrava', '702 00', 'CZ', 1),
    (1, 'shipping', 'Jana Nováková', NULL, 'Příkladná 12', 'Ostrava', '702 00', 'CZ', 1),
    (2, 'billing', 'John Smith', NULL, 'Example Street 5', 'Praha', '110 00', 'CZ', 1),
    (2, 'shipping', 'John Smith', NULL, 'Example Street 5', 'Praha', '110 00', 'CZ', 1);

INSERT INTO shop_order (id, public_id, order_number, customer_id, email, status, locale, currency, prices_include_tax, subtotal_net_minor, tax_total_minor, grand_total_minor, billing_address, shipping_address, customer_note, placed_at, created_at) VALUES
    (1, '01J9ZK3Q8M5N7V2R4T6X8B0D3F', '2026-000001', 1, 'jana.novakova@example.com', 'paid', 'cs', 'CZK', 1, 42893, 11072, 63800, '{"name": "Jana"}', '{"name": "Jana"}', NULL, '2026-11-02 10:15:00', '2026-11-02 10:12:00'),
    (2, '01J9ZK3Q8M5N7V2R4T6X8B0E4G', '2026-000002', 2, 'john.smith@example.com', 'new', 'en', 'EUR', 1, 4288, 950, 5478, '{"name": "John"}', '{"name": "John"}', NULL, '2026-11-03 18:40:00', '2026-11-03 18:36:00');

INSERT INTO shop_order_item (id, shop_order_id, product_id, product_variant_id, sku, product_name, variant_label, attributes_snapshot, quantity, base_unit_price_minor, options_unit_total_minor, tax_rate_bp, line_net_minor, line_tax_minor, line_gross_minor) VALUES
    (1, 1, 1, 2, 'TSHIRT-BK-M', 'Tričko s potiskem', 'Černá / M', '{"color": "black"}', 1.000, 39900, 12000, 2100, 42893, 9007, 51900),
    (2, 2, 2, 8, 'CABLE-CC-2M-BK', 'USB-C cable', 'Black', '{"color": "black"}', 2.000, 799, 0, 2100, 1321, 277, 1598),
    (3, 2, 3, 13, 'GAN-2X65-BK', 'GaN charger', 'Black', '{"color": "black"}', 1.000, 3590, 0, 2100, 2967, 623, 3590);

INSERT INTO shop_order_item_option (shop_order_item_id, option_name, value_label, price_delta_minor) VALUES
    (1, 'Potisk', 'Vlastní potisk', 12000);

INSERT INTO shop_order_adjustment (id, shop_order_id, type, code, title, amount_net_minor, amount_tax_minor, amount_gross_minor, tax_rate_bp) VALUES
    (1, 1, 'shipping', 'courier', 'Kurýr', 9835, 2065, 11900, 2100),
    (2, 2, 'shipping', 'pickup_point', 'Pickup point', 240, 50, 290, 2100);

INSERT INTO shop_order_status_history (shop_order_id, from_status, to_status, note, created_at) VALUES
    (1, NULL, 'new', 'Vytvořeno', '2026-11-02 10:15:00'),
    (1, 'new', 'paid', 'Platba potvrzena', '2026-11-02 10:16:00'),
    (2, NULL, 'new', 'Created', '2026-11-03 18:40:00');

INSERT INTO translation_message (domain, message_key, locale, message_text, source, is_customized) VALUES
    ('catalog', 'button.add_to_cart', 'cs', 'Přidat do košíku', 'core', 0),
    ('catalog', 'button.add_to_cart', 'en', 'Add to cart', 'core', 0);