--TEST--
Catalog admin list filter validates category and pagination boundaries
--FILE--
<?php

use App\Modules\Catalog\Api\Dto\ProductAdminListFilter;
use App\Modules\Catalog\Api\ProductAdminStatus;
use App\Modules\Catalog\Api\ProductAdminType;
use Tester\Assert;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$default = new ProductAdminListFilter();
Assert::same(null, $default->categoryId);
Assert::same(null, $default->status);
Assert::same(null, $default->type);
Assert::same(1, $default->page);
Assert::same(12, $default->perPage);

$filtered = new ProductAdminListFilter(
    categoryId: 7,
    status: ProductAdminStatus::Archived,
    type: ProductAdminType::Digital,
    page: 3,
    perPage: 24,
);
Assert::same(7, $filtered->categoryId);
Assert::same(ProductStatus::Archived, $filtered->status);
Assert::same(ProductType::Digital, $filtered->type);
Assert::same(3, $filtered->page);
Assert::same(24, $filtered->perPage);

$invalid = [
    static fn () => new ProductAdminListFilter(categoryId: 0),
    static fn () => new ProductAdminListFilter(page: 0),
    static fn () => new ProductAdminListFilter(perPage: 0),
    static fn () => new ProductAdminListFilter(perPage: 101),
    static fn () => new ProductAdminListFilter(page: intdiv(PHP_INT_MAX, 2) + 1, perPage: 2),
];

foreach ($invalid as $factory) {
    Assert::exception($factory, InvalidArgumentException::class);
}

echo "OK\n";
?>
--EXPECT--
OK
