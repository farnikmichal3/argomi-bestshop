--TEST--
Demo data contains complete bilingual product text, immutable order snapshots, opening stock movements and system translations
--FILE--
<?php

use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);

/** @var Explorer $database */
$database = $integration->database;
$pdo = $database->getConnection()->getPdo();

// Každý demo produkt musí mít úplný český i anglický text včetně SEO polí.
$productTranslations = (int) $pdo->query(
    "SELECT COUNT(*)
       FROM product_translation pt
       INNER JOIN product p ON p.id = pt.product_id
      WHERE p.code IN ('tshirt-printed', 'usb-c-cable', 'gan-charger')
        AND pt.locale IN ('cs', 'en')
        AND CHAR_LENGTH(TRIM(pt.short_description)) >= 40
        AND CHAR_LENGTH(TRIM(pt.description)) >= 100
        AND pt.meta_title IS NOT NULL
        AND CHAR_LENGTH(TRIM(pt.meta_title)) > 0
        AND pt.meta_description IS NOT NULL
        AND CHAR_LENGTH(TRIM(pt.meta_description)) >= 40",
)->fetchColumn();

Assert::same(6, $productTranslations, 'All three demo products must have complete cs/en descriptions and SEO metadata.');

$orders = $pdo->query(
    "SELECT id, order_number, currency, locale, placed_at,
            adjustments_net_minor, subtotal_net_minor, tax_total_minor, grand_total_minor,
            JSON_CONTAINS_PATH(billing_address, 'all',
                '$.name', '$.street', '$.city', '$.postal_code', '$.country_code') AS billing_complete,
            JSON_CONTAINS_PATH(shipping_address, 'all',
                '$.name', '$.street', '$.city', '$.postal_code', '$.country_code') AS shipping_complete
       FROM shop_order
      WHERE order_number IN ('2026-000003', '2026-000004')
      ORDER BY order_number",
)->fetchAll(PDO::FETCH_ASSOC);

Assert::same(2, count($orders), 'Both complete demo orders must exist.');

$expectedOrders = [
    '2026-000003' => ['CZK', 'cs', 42893, 9835, 11072, 63800, 1],
    '2026-000004' => ['EUR', 'en', 4288, 240, 950, 5478, 2],
];

foreach ($orders as $order) {
    $expected = $expectedOrders[$order['order_number']] ?? null;
    Assert::notSame(null, $expected, 'Unexpected demo order number.');
    Assert::same($expected[0], $order['currency']);
    Assert::same($expected[1], $order['locale']);
    Assert::notSame(null, $order['placed_at'], 'Demo order must be closed only after its snapshots are complete.');
    Assert::same(1, (int) $order['billing_complete'], 'Billing address snapshot must contain all required address fields.');
    Assert::same(1, (int) $order['shipping_complete'], 'Shipping address snapshot must contain all required address fields.');
    Assert::same($expected[2], (int) $order['subtotal_net_minor']);
    Assert::same($expected[3], (int) $order['adjustments_net_minor']);
    Assert::same($expected[4], (int) $order['tax_total_minor']);
    Assert::same($expected[5], (int) $order['grand_total_minor']);

    $items = $pdo->prepare(
        'SELECT COUNT(*) AS item_count,
                SUM(line_net_minor) AS line_net,
                SUM(line_tax_minor) AS line_tax,
                SUM(line_gross_minor) AS line_gross,
                SUM(line_gross_minor - line_net_minor - line_tax_minor) AS invalid_lines,
                SUM(attributes_snapshot IS NULL) AS missing_attributes
           FROM shop_order_item
          WHERE shop_order_id = ?',
    );
    $items->execute([(int) $order['id']]);
    $itemTotals = $items->fetch(PDO::FETCH_ASSOC);

    Assert::same($expected[6], (int) $itemTotals['item_count']);
    Assert::same($expected[2], (int) $itemTotals['line_net']);
    Assert::same($expected[4] - ($order['order_number'] === '2026-000003' ? 2065 : 50), (int) $itemTotals['line_tax']);
    Assert::same(0, (int) $itemTotals['invalid_lines']);
    Assert::same(0, (int) $itemTotals['missing_attributes']);

    $adjustments = $pdo->prepare(
        'SELECT SUM(amount_net_minor) AS net_total,
                SUM(amount_tax_minor) AS tax_total,
                SUM(amount_gross_minor) AS gross_total
           FROM shop_order_adjustment
          WHERE shop_order_id = ?',
    );
    $adjustments->execute([(int) $order['id']]);
    $adjustmentTotals = $adjustments->fetch(PDO::FETCH_ASSOC);

    Assert::same($expected[3], (int) $adjustmentTotals['net_total']);
    Assert::same($order['order_number'] === '2026-000003' ? 2065 : 50, (int) $adjustmentTotals['tax_total']);
    Assert::same(
        $expected[3] + (int) $adjustmentTotals['tax_total'],
        (int) $adjustmentTotals['gross_total'],
    );
}

// Volby produktu jsou uloženy jako součást historického snapshotu, nikoli odkaz na aktuální katalog.
$optionCount = (int) $pdo->query(
    "SELECT COUNT(*)
       FROM shop_order_item_option option_snapshot
       INNER JOIN shop_order_item item ON item.id = option_snapshot.shop_order_item_id
       INNER JOIN shop_order order_snapshot ON order_snapshot.id = item.shop_order_id
      WHERE order_snapshot.order_number = '2026-000003'",
)->fetchColumn();
Assert::same(1, $optionCount, 'The T-shirt order must preserve its selected print option.');

// Každá varianta s kladnou počáteční zásobou musí mít právě jeden příjem.
$expectedOpeningMovementCount = (int) $pdo->query(
    "SELECT COUNT(*)
       FROM stock_item stock
       INNER JOIN product_variant variant ON variant.id = stock.product_variant_id
       INNER JOIN product product ON product.id = variant.product_id
      WHERE product.code IN ('tshirt-printed', 'usb-c-cable', 'gan-charger')
        AND stock.quantity_on_hand > 0",
)->fetchColumn();

$openingMovementCount = (int) $pdo->query(
    "SELECT COUNT(*)
       FROM stock_movement movement
       INNER JOIN product_variant variant ON variant.id = movement.product_variant_id
       INNER JOIN product product ON product.id = variant.product_id
      WHERE movement.reference_type = 'demo_opening_balance'
        AND product.code IN ('tshirt-printed', 'usb-c-cable', 'gan-charger')",
)->fetchColumn();

Assert::same(
    $expectedOpeningMovementCount,
    $openingMovementCount,
    'Every demo variant with positive opening stock must have exactly one receipt movement.',
);

$stockMismatches = (int) $pdo->query(
    "SELECT COUNT(*)
       FROM stock_item stock
       INNER JOIN product_variant variant ON variant.id = stock.product_variant_id
       INNER JOIN product product ON product.id = variant.product_id
       LEFT JOIN stock_movement movement
         ON movement.product_variant_id = stock.product_variant_id
        AND movement.reference_type = 'demo_opening_balance'
      WHERE product.code IN ('tshirt-printed', 'usb-c-cable', 'gan-charger')
        AND (
            (stock.quantity_on_hand > 0 AND movement.id IS NULL)
            OR (stock.quantity_on_hand > 0 AND movement.delta <> stock.quantity_on_hand)
            OR (stock.quantity_on_hand = 0 AND movement.id IS NOT NULL)
        )",
)->fetchColumn();
Assert::same(0, $stockMismatches, 'Opening stock movements must represent the seeded on-hand quantities exactly.');

// Systémové texty jsou dostupné v obou základních jazycích.
$systemTranslations = (int) $pdo->query(
    "SELECT COUNT(*)
       FROM translation_message
      WHERE (domain, message_key) IN (
          ('catalog', 'button.view_details'),
          ('catalog', 'stock.in_stock'),
          ('catalog', 'stock.out_of_stock'),
          ('cart', 'empty.title'),
          ('cart', 'empty.message'),
          ('checkout', 'button.place_order'),
          ('checkout', 'confirmation.title'),
          ('checkout', 'confirmation.message')
      )
        AND locale IN ('cs', 'en')
        AND CHAR_LENGTH(TRIM(message_text)) > 0",
)->fetchColumn();
Assert::same(16, $systemTranslations, 'All seeded system messages must have Czech and English values.');

echo "OK\n";
?>
--EXPECT--
OK
