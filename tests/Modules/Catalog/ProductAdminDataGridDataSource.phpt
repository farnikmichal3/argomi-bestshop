<?php

declare(strict_types=1);

use App\Modules\Catalog\Api\Dto\ProductAdminListFilter;
use App\Modules\Catalog\Api\Dto\ProductAdminListItem;
use App\Modules\Catalog\Api\Dto\ProductAdminListing;
use App\Modules\Catalog\Api\ProductAdminListReader;
use App\Modules\Catalog\Api\ProductAdminStatus;
use App\Modules\Catalog\Api\ProductAdminType;
use App\Shared\Domain\Locale\Locale;
use App\UI\Admin\Products\ProductAdminDataGridDataSource;
use Contributte\Datagrid\Datagrid;
use Contributte\Datagrid\Utils\Sorting;
use Nette\Localization\Translator;
use Tester\Assert;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';

$reader = new class implements ProductAdminListReader {
    /** @var list<ProductAdminListFilter> */
    public array $filters = [];

    public function findProducts(ProductAdminListFilter $filter, Locale $locale): ProductAdminListing
    {
        $this->filters[] = $filter;

        return new ProductAdminListing(
            [
                new ProductAdminListItem(
                    25,
                    'SKU-25',
                    'Test product',
                    ProductAdminType::Digital,
                    ProductAdminStatus::Active,
                    ['Software'],
                ),
            ],
            25,
            $filter->page,
            $filter->perPage,
            3,
        );
    }
};

$translator = new class implements Translator {
    public function translate(mixed $message, mixed ...$parameters): string
    {
        return (string) $message;
    }
};

$grid = new Datagrid();
$category = $grid->addFilterSelect('categoryId', 'Category', [7 => 'Software']);
$status = $grid->addFilterSelect('status', 'Status', ['active' => 'Active']);
$type = $grid->addFilterSelect('type', 'Type', ['digital' => 'Digital']);
$category->setValue('7');
$status->setValue('active');
$type->setValue('digital');

$source = new ProductAdminDataGridDataSource(
    $reader,
    Locale::fromString('cs'),
    $translator,
);
$source->filter([$category, $status, $type]);

Assert::same(25, $source->getCount());
Assert::same(7, $reader->filters[0]->categoryId);
Assert::same(ProductAdminStatus::Active, $reader->filters[0]->status);
Assert::same(ProductAdminType::Digital, $reader->filters[0]->type);

$source->sort(new Sorting([]))->limit(12, 12);
$rows = iterator_to_array($source->getData());

Assert::same(2, $reader->filters[1]->page);
Assert::same(12, $reader->filters[1]->perPage);
Assert::same(25, $rows[0]['id']);
Assert::same('SKU-25', $rows[0]['code']);
Assert::same('Software', $rows[0]['categories']);
Assert::same('products.admin.type.digital', $rows[0]['type']);
Assert::same('products.admin.status.active', $rows[0]['status']);

echo "OK\n";
?>
--EXPECT--
OK
