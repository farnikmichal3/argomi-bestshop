<?php

declare(strict_types=1);

namespace App\UI\Admin\Products;

use App\Modules\Catalog\Api\CategoryTreeItem;
use App\Modules\Catalog\Api\CategoryTreeReader;
use App\Modules\Catalog\Api\ProductAdminListReader;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Shared\Domain\Locale\Locale;
use App\UI\Admin\DataGrid\AdminDataGridFactory;
use App\UI\Admin\DataGrid\AdminDataGridTranslator;
use Nette\Application\UI\Control;
use Nette\ComponentModel\IContainer;

final readonly class ProductAdminDataGridFactory
{
    public function __construct(
        private AdminDataGridFactory $dataGridFactory,
        private ProductAdminListReader $productReader,
        private CategoryTreeReader $categoryTreeReader,
        private TranslatorFactory $translatorFactory,
    ) {}

    public function create(IContainer $parent, string $name, Locale $locale): Control
    {
        $catalogTranslator = $this->translatorFactory->createTranslator(TranslationDomain::Catalog, $locale);
        $adminTranslator = $this->translatorFactory->createTranslator(TranslationDomain::Admin, $locale);
        $translator = new AdminDataGridTranslator($catalogTranslator, $adminTranslator);

        $source = new ProductAdminDataGridDataSource(
            $this->productReader,
            $locale,
            $catalogTranslator,
        );

        $grid = $this->dataGridFactory->create($parent, $name, $source, $translator);

        $grid->addColumnNumber('id', 'products.admin.column.id');
        $grid->addColumnText('code', 'products.admin.column.code');
        $grid->addColumnText('name', 'products.admin.column.name');
        $grid->addColumnText('type', 'products.admin.column.type');
        $grid->addColumnText('status', 'products.admin.column.status');
        $grid->addColumnText('categories', 'products.admin.column.categories');

        $categoryOptions = [];
        $this->appendCategoryOptions(
            $this->categoryTreeReader->findTree($locale),
            0,
            $categoryOptions,
        );

        $allLabel = (string) $catalogTranslator->translate('products.admin.filter.all');

        $grid->addFilterSelect(
            'categoryId',
            'products.admin.filter.category',
            $categoryOptions,
            'categoryId',
        )->setPrompt($allLabel);

        $statusOptions = [];
        foreach (\App\Modules\Catalog\Api\ProductAdminStatus::cases() as $status) {
            $statusOptions[$status->value] = (string) $catalogTranslator->translate('products.admin.status.' . $status->value);
        }

        $grid->addFilterSelect(
            'status',
            'products.admin.filter.status',
            $statusOptions,
            'status',
        )->setPrompt($allLabel);

        $typeOptions = [];
        foreach (\App\Modules\Catalog\Api\ProductAdminType::cases() as $type) {
            $typeOptions[$type->value] = (string) $catalogTranslator->translate('products.admin.type.' . $type->value);
        }

        $grid->addFilterSelect(
            'type',
            'products.admin.filter.type',
            $typeOptions,
            'type',
        )->setPrompt($allLabel);

        return $grid;
    }

    /**
     * @param list<CategoryTreeItem> $nodes
     * @param array<int, string> $options
     */
    private function appendCategoryOptions(array $nodes, int $depth, array &$options): void
    {
        foreach ($nodes as $node) {
            $options[$node->id] = str_repeat('— ', $depth) . ($node->name ?? $node->code);
            $this->appendCategoryOptions($node->children, $depth + 1, $options);
        }
    }
}
