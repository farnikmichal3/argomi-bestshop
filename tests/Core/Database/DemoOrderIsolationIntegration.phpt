--TEST--
Demo orders cannot emit operational Outbox events or become live orders
--FILE--
<?php

use App\Shared\Application\Outbox\OutboxWriter;
use App\Shared\Application\Transaction\Transaction;
use App\Shared\Domain\PublicId\PublicId;
use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
$container = $integration->container;

/** @var Explorer $database */
$database = $integration->database;

/** @var Transaction $transaction */
$transaction = $container->getByType(Transaction::class);

/** @var OutboxWriter $writer */
$writer = $container->getByType(OutboxWriter::class);

$pdo = $database->getConnection()->getPdo();
$demoOrderId = (int) $pdo->query(
    "SELECT id FROM shop_order WHERE order_number = '2026-000003' AND is_demo = 1",
)->fetchColumn();

Assert::true($demoOrderId > 0, 'The demo order must be explicitly classified.');

$liveOrderNumber = 'TEST-DEMO-ISOLATION';
$liveOrderId = 0;
$messageId = null;
$stockMovementId = 0;

try {
    $liveOrder = $database->table('shop_order')->insert([
        'public_id' => strtoupper(bin2hex(random_bytes(13))),
        'order_number' => $liveOrderNumber,
        'customer_id' => null,
        'email' => 'integration@example.test',
        'status' => 'new',
        'locale' => 'cs',
        'currency' => 'CZK',
        'prices_include_tax' => 1,
        'subtotal_net_minor' => 0,
        'adjustments_net_minor' => 0,
        'tax_total_minor' => 0,
        'grand_total_minor' => 0,
        'billing_address' => '{}',
        'shipping_address' => '{}',
        'placed_at' => null,
    ]);
    $liveOrderId = (int) $liveOrder->id;

    $demoVariantId = (int) $pdo->query(
        'SELECT product_variant_id FROM shop_order_item WHERE shop_order_id = ' . $demoOrderId . ' LIMIT 1',
    )->fetchColumn();

    // Skladový pohyb běžné objednávky je povolen a explicitně odkazuje na objednávku.
    $stockMovement = $database->table('stock_movement')->insert([
        'product_variant_id' => $demoVariantId,
        'delta' => -1.000,
        'reason' => 'sale',
        'reference_type' => 'shop_order',
        'reference_id' => $liveOrderId,
        'note' => 'Integration test',
    ]);
    $stockMovementId = (int) $stockMovement->id;

    // Demo objednávka nesmí vytvářet skutečný skladový pohyb.
    Assert::exception(
        static fn () => $database->table('stock_movement')->insert([
            'product_variant_id' => $demoVariantId,
            'delta' => -1.000,
            'reason' => 'sale',
            'reference_type' => 'shop_order',
            'reference_id' => $demoOrderId,
            'note' => 'Must be rejected',
        ]),
        Throwable::class,
    );

    // Ani existující skladový pohyb nelze přesměrovat na demo objednávku.
    Assert::exception(
        static fn () => $database->table('stock_movement')
            ->where('id', $stockMovementId)
            ->update(['reference_id' => $demoOrderId]),
        Throwable::class,
    );

    // Běžná objednávka může emitovat událost; její Outbox záznam nese FK.
    $messageId = $transaction->run(
        static fn (): PublicId => $writer->record(
            'order.test.live',
            ['orderId' => $liveOrderId],
            $liveOrderId,
        ),
    );

    $message = $database->table('outbox_message')
        ->where('message_id', $messageId->toString())
        ->fetch();

    Assert::notNull($message);
    Assert::same($liveOrderId, (int) $message->shop_order_id);
    Assert::same('pending', $message->status);

    // Ani dodatečné přesměrování existující zprávy na demo objednávku není možné.
    Assert::exception(
        static fn () => $database->table('outbox_message')
            ->where('message_id', $messageId->toString())
            ->update(['shop_order_id' => $demoOrderId]),
        Throwable::class,
    );

    // Objednávková zpráva nesmí ztratit vazbu na objednávku.
    Assert::exception(
        static fn () => $database->table('outbox_message')
            ->where('message_id', $messageId->toString())
            ->update(['event_name' => 'order.test.missing-link', 'shop_order_id' => null]),
        Throwable::class,
    );

    // Payload nesmí odkazovat na jinou objednávku než chráněný FK.
    Assert::exception(
        static fn () => $transaction->run(
            static fn () => $writer->record(
                'order.test.mismatched-order',
                ['orderId' => $demoOrderId],
                $liveOrderId,
            ),
        ),
        Throwable::class,
    );

    // Demo objednávka nesmí vložit provozní událost do Outboxu.
    Assert::exception(
        static fn () => $transaction->run(
            static fn () => $writer->record(
                'order.test.demo',
                ['orderId' => $demoOrderId],
                $demoOrderId,
            ),
        ),
        Throwable::class,
    );

    // Objednávková událost bez explicitního FK je neplatná.
    Assert::exception(
        static fn () => $transaction->run(
            static fn () => $writer->record('order.test.missing-link', []),
        ),
        Throwable::class,
    );

    // Klasifikaci nelze dodatečně změnit ani u běžné objednávky.
    Assert::exception(
        static fn () => $database->table('shop_order')
            ->where('id', $liveOrderId)
            ->update(['is_demo' => 1]),
        Throwable::class,
    );

    // Demo objednávku nelze převést zpět na běžnou.
    Assert::exception(
        static fn () => $database->table('shop_order')
            ->where('id', $demoOrderId)
            ->update(['is_demo' => 0]),
        Throwable::class,
    );
} finally {
    if ($messageId instanceof PublicId) {
        $database->table('outbox_message')
            ->where('message_id', $messageId->toString())
            ->delete();
    }

    if ($stockMovementId > 0) {
        $database->table('stock_movement')
            ->where('id', $stockMovementId)
            ->delete();
    }

    if ($liveOrderId > 0) {
        $database->table('shop_order')
            ->where('id', $liveOrderId)
            ->delete();
    }
}

echo "OK\n";
?>
--EXPECT--
OK
