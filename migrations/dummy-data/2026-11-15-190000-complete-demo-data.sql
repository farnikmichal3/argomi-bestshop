SET NAMES utf8mb4;

-- Plné produktové texty jsou uloženy v obou podporovaných jazycích.
UPDATE product_translation AS pt
INNER JOIN (
    SELECT 1 AS product_id, 'cs' AS locale,
           'Bavlněné tričko s potiskem pro každý den' AS short_description,
           '<p>Pohodlné bavlněné tričko pro každodenní nošení. Konkrétní varianta určuje barvu a velikost.</p><ul><li>Materiál: bavlna</li><li>Barvy: černá a bílá</li><li>Velikosti: S, M a L</li><li>Hmotnost varianty přibližně 180 až 200 g</li></ul><p>Pro zachování potisku perte tričko naruby podle pokynů na štítku.</p>' AS description,
           'Bavlněné tričko s potiskem | ArgoMi' AS meta_title,
           'Pohodlné bavlněné tričko v černé nebo bílé barvě a velikostech S až L. Vyberte si variantu a vlastní potisk.' AS meta_description
    UNION ALL
    SELECT 1, 'en',
           'Printed cotton T-shirt for everyday wear',
           '<p>A comfortable cotton T-shirt for everyday wear. The selected variant determines its colour and size.</p><ul><li>Material: cotton</li><li>Colours: black and white</li><li>Sizes: S, M and L</li><li>Variant weight approximately 180 to 200 g</li></ul><p>To protect the print, wash the T-shirt inside out and follow the care label.</p>',
           'Printed cotton T-shirt | ArgoMi',
           'A comfortable cotton T-shirt in black or white and sizes S to L. Choose a variant and add your own print.'
    UNION ALL
    SELECT 2, 'cs',
           'Odolný USB-C kabel v délkách 1 m a 2 m',
           '<p>Odolný nabíjecí a datový kabel pro zařízení s USB-C. Nabídka zahrnuje délku 1 m nebo 2 m a varianty s USB-C na obou koncích nebo s USB-A na straně zdroje.</p><ul><li>Délky: 1 m a 2 m</li><li>Barvy: černá a bílá</li><li>Konektory podle varianty: USB-C/USB-C nebo USB-A/USB-C</li></ul><p>Před nákupem zkontrolujte typ konektoru na nabíječce a připojovaném zařízení.</p>',
           'USB-C kabel 1 m a 2 m | ArgoMi',
           'Odolný USB-C kabel v délce 1 m nebo 2 m. Vyberte si černou či bílou variantu a správnou kombinaci konektorů.'
    UNION ALL
    SELECT 2, 'en',
           'Durable USB-C cable in 1 m and 2 m lengths',
           '<p>A durable charging and data cable for USB-C devices. Choose a 1 m or 2 m cable, with USB-C connectors on both ends or USB-A on the power-source end.</p><ul><li>Lengths: 1 m and 2 m</li><li>Colours: black and white</li><li>Connector options: USB-C to USB-C or USB-A to USB-C</li></ul><p>Check the connector type on your charger and device before ordering.</p>',
           'USB-C cable 1 m and 2 m | ArgoMi',
           'A durable USB-C cable in 1 m or 2 m lengths. Choose black or white and the connector combination you need.'
    UNION ALL
    SELECT 3, 'cs',
           'Kompaktní síťová nabíječka s technologií GaN',
           '<p>Kompaktní síťová nabíječka s technologií GaN. Jednotlivé varianty se liší počtem výstupních portů a celkovým výkonem.</p><ul><li>Výkon variant: 65 W nebo 100 W</li><li>Počet portů: 1 až 3</li><li>Barvy podle varianty: bílá nebo černá</li></ul><p>Celkový výkon se při současném nabíjení více zařízení rozděluje podle konkrétní varianty.</p>',
           'GaN nabíječka 65 W a 100 W | ArgoMi',
           'Kompaktní GaN nabíječka s výkonem 65 W nebo 100 W. Vyberte si variantu podle počtu portů a barvy.'
    UNION ALL
    SELECT 3, 'en',
           'Compact GaN wall charger',
           '<p>A compact wall charger featuring GaN technology. Individual variants differ in the number of output ports and total power.</p><ul><li>Available power ratings: 65 W or 100 W</li><li>Number of ports: 1 to 3</li><li>Variant colours: white or black</li></ul><p>When charging multiple devices, the available total power is shared according to the selected variant.</p>',
           '65 W and 100 W GaN charger | ArgoMi',
           'A compact GaN charger rated at 65 W or 100 W. Choose the variant by port count and colour.'
) AS demo
    ON demo.product_id = pt.product_id
   AND demo.locale = pt.locale
SET pt.short_description = demo.short_description,
    pt.description = demo.description,
    pt.meta_title = demo.meta_title,
    pt.meta_description = demo.meta_description
WHERE pt.product_id IN (1, 2, 3)
  AND pt.locale IN ('cs', 'en');

-- Počáteční pohyb skladu zachycuje stav založený v původních demo datech.
-- Záznamy vznikají až po starších ukázkových objednávkách, které tento stav předcházejí.
INSERT INTO stock_movement
    (product_variant_id, delta, reason, reference_type, reference_id, note, created_at)
SELECT
    si.product_variant_id,
    si.quantity_on_hand,
    'receipt',
    'demo_opening_balance',
    1,
    'Počáteční demo stav skladu',
    '2026-11-04 00:00:00'
FROM stock_item AS si
INNER JOIN product_variant AS pv
    ON pv.id = si.product_variant_id
INNER JOIN product AS p
    ON p.id = pv.product_id
WHERE p.code IN ('tshirt-printed', 'usb-c-cable', 'gan-charger')
  AND si.quantity_on_hand > 0
  AND NOT EXISTS (
      SELECT 1
      FROM stock_movement AS existing
      WHERE existing.product_variant_id = si.product_variant_id
        AND existing.reference_type = 'demo_opening_balance'
  );

-- Objednávky se nejprve vytvoří jako otevřené. Jejich položky, volby
-- a úpravy se uloží před nastavením placed_at, které snapshot uzamkne.
INSERT INTO shop_order
    (public_id, order_number, customer_id, email, status, locale, currency,
     prices_include_tax, subtotal_net_minor, adjustments_net_minor,
     tax_total_minor, grand_total_minor, billing_address, shipping_address,
     customer_note, placed_at, created_at)
VALUES
    ('01J9ZK3Q8M5N7V2R4T6X8B0F5H', '2026-000003', 1, 'jana.novakova@example.com',
     'new', 'cs', 'CZK', 1, 42893, 0, 9007, 51900,
     JSON_OBJECT(
         'name', 'Jana Nováková', 'company', NULL, 'street', 'Příkladná 12',
         'city', 'Ostrava', 'postal_code', '702 00', 'country_code', 'CZ',
         'company_id', NULL, 'vat_id', NULL, 'phone', NULL
     ),
     JSON_OBJECT(
         'name', 'Jana Nováková', 'company', NULL, 'street', 'Příkladná 12',
         'city', 'Ostrava', 'postal_code', '702 00', 'country_code', 'CZ',
         'company_id', NULL, 'vat_id', NULL, 'phone', NULL
     ),
     'Prosím o pečlivé zabalení.', NULL, '2026-11-05 10:12:00'),
    ('01J9ZK3Q8M5N7V2R4T6X8B0G6J', '2026-000004', 2, 'john.smith@example.com',
     'new', 'en', 'EUR', 1, 4288, 0, 900, 5188,
     JSON_OBJECT(
         'name', 'John Smith', 'company', NULL, 'street', 'Example Street 5',
         'city', 'Praha', 'postal_code', '110 00', 'country_code', 'CZ',
         'company_id', NULL, 'vat_id', NULL, 'phone', NULL
     ),
     JSON_OBJECT(
         'name', 'John Smith', 'company', NULL, 'street', 'Example Street 5',
         'city', 'Praha', 'postal_code', '110 00', 'country_code', 'CZ',
         'company_id', NULL, 'vat_id', NULL, 'phone', NULL
     ),
     NULL, NULL, '2026-11-06 18:36:00');

INSERT INTO shop_order_item
    (shop_order_id, product_id, product_variant_id, sku, product_name,
     variant_label, attributes_snapshot, quantity, base_unit_price_minor,
     options_unit_total_minor, tax_rate_bp, line_net_minor, line_tax_minor,
     line_gross_minor)
SELECT
    o.id, 1, 2, 'TSHIRT-BK-M', 'Tričko s potiskem', 'Černá / M',
    JSON_OBJECT(
        'color', JSON_OBJECT('code', 'black', 'label', 'Černá'),
        'size', JSON_OBJECT('code', 'm', 'label', 'M')
    ),
    1.000, 39900, 12000, 2100, 42893, 9007, 51900
FROM shop_order AS o
WHERE o.order_number = '2026-000003';

INSERT INTO shop_order_item
    (shop_order_id, product_id, product_variant_id, sku, product_name,
     variant_label, attributes_snapshot, quantity, base_unit_price_minor,
     options_unit_total_minor, tax_rate_bp, line_net_minor, line_tax_minor,
     line_gross_minor)
SELECT
    o.id, 2, 8, 'CABLE-CC-2M-BK', 'USB-C cable', 'Black / 2 m / USB-C to USB-C',
    JSON_OBJECT(
        'length_m', JSON_OBJECT('value', 2, 'unit', 'm'),
        'c_connectors', JSON_OBJECT('value', 2),
        'color', JSON_OBJECT('code', 'black', 'label', 'Black')
    ),
    2.000, 799, 0, 2100, 1321, 277, 1598
FROM shop_order AS o
WHERE o.order_number = '2026-000004';

INSERT INTO shop_order_item
    (shop_order_id, product_id, product_variant_id, sku, product_name,
     variant_label, attributes_snapshot, quantity, base_unit_price_minor,
     options_unit_total_minor, tax_rate_bp, line_net_minor, line_tax_minor,
     line_gross_minor)
SELECT
    o.id, 3, 13, 'GAN-2X65-BK', 'GaN charger', 'Black / 65 W / 2 ports',
    JSON_OBJECT(
        'slots', JSON_OBJECT('value', 2),
        'power_w', JSON_OBJECT('value', 65, 'unit', 'W'),
        'color', JSON_OBJECT('code', 'black', 'label', 'Black')
    ),
    1.000, 3590, 0, 2100, 2967, 623, 3590
FROM shop_order AS o
WHERE o.order_number = '2026-000004';

INSERT INTO shop_order_item_option
    (shop_order_item_id, option_name, value_label, price_delta_minor)
SELECT
    item.id, 'Potisk', 'Vlastní potisk', 12000
FROM shop_order_item AS item
INNER JOIN shop_order AS o
    ON o.id = item.shop_order_id
WHERE o.order_number = '2026-000003'
  AND item.sku = 'TSHIRT-BK-M';

INSERT INTO shop_order_adjustment
    (shop_order_id, type, code, title, amount_net_minor,
     amount_tax_minor, amount_gross_minor, tax_rate_bp)
SELECT id, 'shipping', 'courier', 'Kurýr', 9835, 2065, 11900, 2100
FROM shop_order
WHERE order_number = '2026-000003';

INSERT INTO shop_order_adjustment
    (shop_order_id, type, code, title, amount_net_minor,
     amount_tax_minor, amount_gross_minor, tax_rate_bp)
SELECT id, 'shipping', 'pickup_point', 'Pickup point', 240, 50, 290, 2100
FROM shop_order
WHERE order_number = '2026-000004';

-- Uzavření proběhne až po vložení všech částí účetního snapshotu.
UPDATE shop_order
SET status = 'paid',
    adjustments_net_minor = 9835,
    tax_total_minor = 11072,
    grand_total_minor = 63800,
    placed_at = '2026-11-05 10:15:00'
WHERE order_number = '2026-000003';

UPDATE shop_order
SET status = 'new',
    adjustments_net_minor = 240,
    tax_total_minor = 950,
    grand_total_minor = 5478,
    placed_at = '2026-11-06 18:40:00'
WHERE order_number = '2026-000004';

INSERT INTO shop_order_status_history
    (shop_order_id, from_status, to_status, note, created_at)
SELECT id, NULL, 'new', 'Demo objednávka vytvořena', '2026-11-05 10:15:00'
FROM shop_order
WHERE order_number = '2026-000003';

INSERT INTO shop_order_status_history
    (shop_order_id, from_status, to_status, note, created_at)
SELECT id, 'new', 'paid', 'Demo platba potvrzena', '2026-11-05 10:16:00'
FROM shop_order
WHERE order_number = '2026-000003';

INSERT INTO shop_order_status_history
    (shop_order_id, from_status, to_status, note, created_at)
SELECT id, NULL, 'new', 'Demo objednávka vytvořena', '2026-11-06 18:40:00'
FROM shop_order
WHERE order_number = '2026-000004';

-- Základní systémové texty pro katalog, košík a dokončení objednávky.
INSERT INTO translation_message
    (domain, message_key, locale, message_text, source, is_customized, default_hash)
VALUES
    ('catalog', 'button.view_details', 'cs', 'Zobrazit detail', 'core', 0, SHA1('Zobrazit detail')),
    ('catalog', 'button.view_details', 'en', 'View details', 'core', 0, SHA1('View details')),
    ('catalog', 'stock.in_stock', 'cs', 'Skladem', 'core', 0, SHA1('Skladem')),
    ('catalog', 'stock.in_stock', 'en', 'In stock', 'core', 0, SHA1('In stock')),
    ('catalog', 'stock.out_of_stock', 'cs', 'Není skladem', 'core', 0, SHA1('Není skladem')),
    ('catalog', 'stock.out_of_stock', 'en', 'Out of stock', 'core', 0, SHA1('Out of stock')),
    ('cart', 'empty.title', 'cs', 'Košík je prázdný', 'core', 0, SHA1('Košík je prázdný')),
    ('cart', 'empty.title', 'en', 'Your cart is empty', 'core', 0, SHA1('Your cart is empty')),
    ('cart', 'empty.message', 'cs', 'Přidejte do košíku produkty, které chcete objednat.', 'core', 0, SHA1('Přidejte do košíku produkty, které chcete objednat.')),
    ('cart', 'empty.message', 'en', 'Add the products you want to order to your cart.', 'core', 0, SHA1('Add the products you want to order to your cart.')),
    ('checkout', 'button.place_order', 'cs', 'Objednat s povinností platby', 'core', 0, SHA1('Objednat s povinností platby')),
    ('checkout', 'button.place_order', 'en', 'Place order with obligation to pay', 'core', 0, SHA1('Place order with obligation to pay')),
    ('checkout', 'confirmation.title', 'cs', 'Děkujeme za objednávku', 'core', 0, SHA1('Děkujeme za objednávku')),
    ('checkout', 'confirmation.title', 'en', 'Thank you for your order', 'core', 0, SHA1('Thank you for your order')),
    ('checkout', 'confirmation.message', 'cs', 'Objednávku {orderNumber} jsme přijali.', 'core', 0, SHA1('Objednávku {orderNumber} jsme přijali.')),
    ('checkout', 'confirmation.message', 'en', 'We have received your order {orderNumber}.', 'core', 0, SHA1('We have received your order {orderNumber}.'))
ON DUPLICATE KEY UPDATE
    message_text = IF(is_customized = 0, VALUES(message_text), message_text),
    source = IF(is_customized = 0, VALUES(source), source),
    default_hash = IF(is_customized = 0, VALUES(default_hash), default_hash),
    is_outdated = IF(is_customized = 0, 0, is_outdated);
