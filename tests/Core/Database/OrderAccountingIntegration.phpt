--TEST--
Order accounting totals are validated and placed order snapshots are immutable
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

$token = bin2hex(random_bytes(4));
$orderNumber = 'F3-27-' . strtoupper($token);
$publicId = strtoupper(bin2hex(random_bytes(13)));

$pdo->beginTransaction();

try {
    $insertOrder = $pdo->prepare(
        'INSERT INTO shop_order
            (public_id, order_number, email, status, locale, currency,
             prices_include_tax, subtotal_net_minor, adjustments_net_minor,
             tax_total_minor, grand_total_minor, billing_address, shipping_address)
         VALUES (?, ?, ?, ?, ?, ?, 1, 0, 0, 0, 0, JSON_OBJECT(), JSON_OBJECT())',
    );
    $insertOrder->execute([
        $publicId,
        $orderNumber,
        'f3-27@example.invalid',
        'draft',
        'en',
        'EUR',
    ]);
    $orderId = (int) $pdo->lastInsertId();

    $insertItem = $pdo->prepare(
        'INSERT INTO shop_order_item
            (shop_order_id, sku, product_name, quantity, base_unit_price_minor,
             options_unit_total_minor, tax_rate_bp, line_net_minor, line_tax_minor, line_gross_minor)
         VALUES (?, ?, ?, 1.000, ?, 0, ?, ?, ?, ?)',
    );

    $insertItem->execute([$orderId, 'F3-27-A', 'Test item A', 1000, 2100, 1000, 210, 1210]);
    $itemId = (int) $pdo->lastInsertId();
    $insertItem->execute([$orderId, 'F3-27-B', 'Test item B', 500, 2100, 500, 105, 605]);
    $secondItemId = (int) $pdo->lastInsertId();

    $insertOption = $pdo->prepare(
        'INSERT INTO shop_order_item_option
            (shop_order_item_id, option_name, value_label, price_delta_minor)
         VALUES (?, ?, ?, ?)',
    );
    $insertOption->execute([$itemId, 'Gift wrap', 'Yes', 0]);
    $optionId = (int) $pdo->lastInsertId();

    $insertAdjustment = $pdo->prepare(
        'INSERT INTO shop_order_adjustment
            (shop_order_id, type, code, title, amount_net_minor, amount_tax_minor,
             amount_gross_minor, tax_rate_bp)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
    );
    $insertAdjustment->execute([$orderId, 'shipping', 'shipping', 'Shipping', 100, 21, 121, 2100]);
    $insertAdjustment->execute([$orderId, 'discount', 'discount', 'Discount', -50, -10, -60, 2000]);

    $updateTotals = $pdo->prepare(
        'UPDATE shop_order
         SET subtotal_net_minor = 1500,
             adjustments_net_minor = 50,
             tax_total_minor = 326,
             grand_total_minor = 1876,
             placed_at = CURRENT_TIMESTAMP
         WHERE id = ?',
    );
    $updateTotals->execute([$orderId]);

    $placedOrder = $pdo->prepare(
        'SELECT subtotal_net_minor, adjustments_net_minor, tax_total_minor,
                grand_total_minor, placed_at
         FROM shop_order WHERE id = ?',
    );
    $placedOrder->execute([$orderId]);
    $placed = $placedOrder->fetch(PDO::FETCH_ASSOC);

    Assert::same(1500, (int) $placed['subtotal_net_minor']);
    Assert::same(50, (int) $placed['adjustments_net_minor']);
    Assert::same(326, (int) $placed['tax_total_minor']);
    Assert::same(1876, (int) $placed['grand_total_minor']);
    Assert::notSame(null, $placed['placed_at']);

    $expectRejected = static function (callable $operation, string $message): void {
        $exception = null;

        try {
            $operation();
        } catch (PDOException $caught) {
            $exception = $caught;
        }

        Assert::type(PDOException::class, $exception, $message);
        Assert::same('45000', (string) $exception->getCode(), $message);
    };

    $expectRejected(
        static fn () => $pdo->prepare(
            'UPDATE shop_order SET grand_total_minor = 1877 WHERE id = ?',
        )->execute([$orderId]),
        'A placed order total must not be changed.',
    );

    $expectRejected(
        static fn () => $pdo->prepare(
            'UPDATE shop_order SET placed_at = NULL WHERE id = ?',
        )->execute([$orderId]),
        'A placed order must not be reopened.',
    );

    $expectRejected(
        static fn () => $pdo->prepare(
            'UPDATE shop_order_item SET product_name = ? WHERE id = ?',
        )->execute(['Changed snapshot', $itemId]),
        'A placed order item must not be changed.',
    );

    $expectRejected(
        static fn () => $pdo->prepare(
            'DELETE FROM shop_order_item WHERE id = ?',
        )->execute([$secondItemId]),
        'An item of a placed order must not be deleted.',
    );

    $expectRejected(
        static fn () => $insertItem->execute([
            $orderId, 'F3-27-C', 'Late item', 100, 2100, 100, 21, 121,
        ]),
        'An item must not be added to a placed order.',
    );

    $expectRejected(
        static fn () => $pdo->prepare(
            'UPDATE shop_order_adjustment SET title = ? WHERE shop_order_id = ? AND code = ?',
        )->execute(['Changed shipping', $orderId, 'shipping']),
        'An adjustment of a placed order must not be changed.',
    );

    $expectRejected(
        static fn () => $pdo->prepare(
            'DELETE FROM shop_order_adjustment WHERE shop_order_id = ? AND code = ?',
        )->execute([$orderId, 'discount']),
        'An adjustment of a placed order must not be deleted.',
    );

    $expectRejected(
        static fn () => $insertAdjustment->execute([
            $orderId, 'surcharge', 'late', 'Late surcharge', 10, 2, 12, 2000,
        ]),
        'An adjustment must not be added to a placed order.',
    );

    $expectRejected(
        static fn () => $pdo->prepare(
            'UPDATE shop_order_item_option SET value_label = ? WHERE id = ?',
        )->execute(['No', $optionId]),
        'An option snapshot of a placed order must not be changed.',
    );

    $expectRejected(
        static fn () => $pdo->prepare(
            'DELETE FROM shop_order_item_option WHERE id = ?',
        )->execute([$optionId]),
        'An option snapshot of a placed order must not be deleted.',
    );

    $expectRejected(
        static fn () => $insertOption->execute([$itemId, 'Late option', 'No', 0]),
        'An option must not be added to a placed order item.',
    );

    $expectRejected(
        static fn () => $pdo->prepare(
            'DELETE FROM shop_order WHERE id = ?',
        )->execute([$orderId]),
        'A placed order must not be deleted.',
    );

    $badOrderNumber = 'F3-27-BAD-' . strtoupper($token);
    $badPublicId = strtoupper(bin2hex(random_bytes(13)));
    $insertOrder->execute([
        $badPublicId,
        $badOrderNumber,
        'f3-27-bad@example.invalid',
        'draft',
        'en',
        'EUR',
    ]);
    $badOrderId = (int) $pdo->lastInsertId();
    $insertItem->execute([
        $badOrderId, 'F3-27-BAD', 'Bad totals item', 100, 2100, 100, 21, 121,
    ]);

    $expectRejected(
        static fn () => $pdo->prepare(
            'UPDATE shop_order SET grand_total_minor = 1 WHERE id = ?',
        )->execute([$badOrderId]),
        'The grand total CHECK must also protect draft orders.',
    );

    $expectRejected(
        static fn () => $pdo->prepare(
            'UPDATE shop_order
             SET subtotal_net_minor = 99,
                 adjustments_net_minor = 0,
                 tax_total_minor = 21,
                 grand_total_minor = 120,
                 placed_at = CURRENT_TIMESTAMP
             WHERE id = ?',
        )->execute([$badOrderId]),
        'An order with a subtotal inconsistent with its item snapshots must not be placed.',
    );

    $badLineOrderNumber = 'F3-27-LINE-' . strtoupper($token);
    $badLinePublicId = strtoupper(bin2hex(random_bytes(13)));
    $insertOrder->execute([
        $badLinePublicId,
        $badLineOrderNumber,
        'f3-27-line@example.invalid',
        'draft',
        'en',
        'EUR',
    ]);
    $badLineOrderId = (int) $pdo->lastInsertId();

    Assert::exception(
        static fn () => $insertItem->execute([
            $badLineOrderId, 'F3-27-LINE', 'Invalid line', 100, 2100, 100, 21, 122,
        ]),
        PDOException::class,
    );
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "OK\n";
?>
--EXPECT--
OK
