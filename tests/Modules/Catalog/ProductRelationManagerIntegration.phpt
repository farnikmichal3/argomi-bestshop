--TEST--
Product relation service adds, removes and reorders complete directional relation sets transactionally
--FILE--
<?php

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
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
/** @var Explorer $database */
$database = $integration->database;
$pdo = $database->getConnection()->getPdo();

try {
    // Demo produkty 1, 2 a 3 jsou aktivní. Vztahy se zapisují pouze v jednom směru.
    $service->add(1, 2, ProductRelationType::Accessory, 20);
    $service->add(1, 3, ProductRelationType::Alternative, 10);

    Assert::same(
        2,
        (int) $pdo->query('SELECT COUNT(*) FROM product_relation WHERE product_id = 1 AND related_product_id IN (2, 3)')->fetchColumn(),
    );
    Assert::same(
        0,
        (int) $pdo->query('SELECT COUNT(*) FROM product_relation WHERE product_id IN (2, 3) AND related_product_id = 1')->fetchColumn(),
        'Reverse relations must never be created implicitly.',
    );

    $service->reorder(1, [
        new ProductRelationOrderDto(2, ProductRelationType::Accessory),
        new ProductRelationOrderDto(3, ProductRelationType::Alternative),
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

    Assert::true($service->remove(1, 2, ProductRelationType::Accessory));
    Assert::false($service->remove(1, 2, ProductRelationType::Accessory));
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
