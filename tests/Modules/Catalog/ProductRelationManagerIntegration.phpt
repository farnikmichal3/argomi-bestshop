--TEST--
Product relation service adds, removes and reorders complete directional relation sets transactionally
--FILE--
<?php

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\ProductRelationKind;
use App\Modules\Catalog\Api\Dto\ProductRelationOrder;
use App\Modules\Catalog\Infrastructure\Api\ProductRelationCommandApiService;
use App\Modules\Catalog\Infrastructure\Api\ProductRelationQueryApiService;
use App\Shared\Domain\Locale\Locale;
use App\Modules\Catalog\Application\Dto\ProductRelationOrderDto;
use App\Modules\Catalog\Application\ProductRelationRepository;
use App\Shared\Application\Transaction\Transaction;
use App\Modules\Catalog\Application\ProductRelationService;
use App\Modules\Catalog\Domain\ProductRelationType;
use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);

/** @var ProductRelationRepository $repository */
$repository = $integration->container->getByType(ProductRelationRepository::class);
/** @var Transaction $transaction */
$transaction = $integration->container->getByType(Transaction::class);
$deniedPermission = new class implements CatalogPermission {
    public function canView(): bool { return false; }
    public function canManage(): bool { return false; }
};
$deniedService = new ProductRelationService($repository, $transaction, $deniedPermission);
Assert::exception(
    static fn () => $deniedService->add(1, 2, ProductRelationType::Accessory),
    CatalogAccessDeniedException::class,
);
Assert::exception(
    static fn () => $deniedService->remove(1, 2, ProductRelationType::Accessory),
    CatalogAccessDeniedException::class,
);
Assert::exception(
    static fn () => $deniedService->reorder(1, []),
    CatalogAccessDeniedException::class,
);

$managerPermission = new class implements CatalogPermission {
    public function canView(): bool { return true; }
    public function canManage(): bool { return true; }
};
$service = new ProductRelationService($repository, $transaction, $managerPermission);
$writer = new ProductRelationCommandApiService($service);
$reader = new ProductRelationQueryApiService($repository);
/** @var Explorer $database */
$database = $integration->database;
$pdo = $database->getConnection()->getPdo();

try {
    // Demo produkty 1, 2 a 3 jsou aktivní. Vztahy se zapisují pouze v jednom směru.
    $writer->add(1, 2, ProductRelationKind::Accessory);
    $writer->add(1, 3, ProductRelationKind::Alternative);

    $locale = Locale::fromString('cs');
    $items = $reader->findForProduct(1, $locale);
    Assert::same(2, count($items));
    Assert::same(ProductRelationKind::Accessory, $items[0]->type);
    Assert::true($items[0]->name !== null);
    $available = $reader->findAvailableProducts(1, $locale);
    $availableIds = array_map(static fn ($product): int => $product->id, $available);
    Assert::false(in_array(1, $availableIds, true));
    Assert::true(in_array(2, $availableIds, true));

    Assert::same(
        2,
        (int) $pdo->query('SELECT COUNT(*) FROM product_relation WHERE product_id = 1 AND related_product_id IN (2, 3)')->fetchColumn(),
    );
    Assert::same(
        0,
        (int) $pdo->query('SELECT COUNT(*) FROM product_relation WHERE product_id IN (2, 3) AND related_product_id = 1')->fetchColumn(),
        'Reverse relations must never be created implicitly.',
    );

    $writer->reorder(1, [
        new ProductRelationOrder(2, ProductRelationKind::Accessory),
        new ProductRelationOrder(3, ProductRelationKind::Alternative),
    ]);

    $ordered = $pdo->query(
        'SELECT related_product_id, type, sort_order FROM product_relation WHERE product_id = 1 ORDER BY sort_order, id',
    )->fetchAll(PDO::FETCH_ASSOC);

    Assert::same(2, (int) $ordered[0]['related_product_id']);
    Assert::same('accessory', $ordered[0]['type']);
    Assert::same(0, (int) $ordered[0]['sort_order']);
    Assert::same(3, (int) $ordered[1]['related_product_id']);
    Assert::same('alternative', $ordered[1]['type']);
    Assert::same(1, (int) $ordered[1]['sort_order']);

    Assert::exception(
        static fn () => $service->reorder(1, [
            new ProductRelationOrderDto(2, ProductRelationType::Accessory),
        ]),
        DomainException::class,
        'Nové pořadí musí obsahovat všechny existující vztahy právě jednou.',
    );

    Assert::true($writer->remove(1, 2, ProductRelationKind::Accessory));
    Assert::false($writer->remove(1, 2, ProductRelationKind::Accessory));
    Assert::same(
        1,
        (int) $pdo->query('SELECT COUNT(*) FROM product_relation WHERE product_id = 1 AND related_product_id = 3')->fetchColumn(),
    );

    Assert::exception(
        static fn () => $service->add(1, 1, ProductRelationType::Accessory),
        InvalidArgumentException::class,
    );
} finally {
    // Test neobaluje use-cases vlastní transakcí: každá operace spravuje svou.
    $pdo->exec(
        "DELETE FROM product_relation
         WHERE product_id = 1
           AND ((related_product_id = 2 AND type = 'accessory')
             OR (related_product_id = 3 AND type = 'alternative'))",
    );
}

echo "OK\n";
?>
--EXPECT--
OK
