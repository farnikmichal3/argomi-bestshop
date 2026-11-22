<?php

declare(strict_types=1);

namespace App\UI\Admin\Products;

use App\Modules\Catalog\Api\Dto\ProductAdminListFilter;
use App\Modules\Catalog\Api\ProductAdminListReader;
use App\Modules\Catalog\Api\ProductAdminStatus;
use App\Modules\Catalog\Api\ProductAdminType;
use App\Shared\Domain\Locale\Locale;
use Contributte\Datagrid\DataSource\IDataSource;
use Contributte\Datagrid\Filter\Filter;
use Contributte\Datagrid\Utils\Sorting;
use Nette\Localization\Translator;
use UnexpectedValueException;

/**
 * Adaptér veřejného katalogového read modelu na rozhraní Ublaboo DataGrid.
 *
 * Filtrování, počítání i stránkování zůstává v Catalog read modelu;
 * DataGrid nikdy nedostává přímý přístup k databázi.
 */
final class ProductAdminDataGridDataSource implements IDataSource
{
    private ?int $categoryId = null;

    private ?ProductAdminStatus $status = null;

    private ?ProductAdminType $type = null;

    private int $offset = 0;

    private int $limit = 12;

    private ?int $singleProductId = null;

    public function __construct(
        private readonly ProductAdminListReader $productReader,
        private readonly Locale $locale,
        private readonly Translator $translator,
    ) {}

    /**
     * @param array<Filter> $filters
     */
    public function filter(array $filters): void
    {
        $this->categoryId = null;
        $this->status = null;
        $this->type = null;

        foreach ($filters as $filter) {
            if (!$filter->isValueSet()) {
                continue;
            }

            $value = $filter->getValue();

            if ($value === null || $value === '') {
                continue;
            }

            switch ($filter->getKey()) {
                case 'categoryId':
                    $this->categoryId = $this->parsePositiveInteger($value, 'categoryId');
                    break;

                case 'status':
                    if (!is_string($value) || ProductAdminStatus::tryFrom($value) === null) {
                        throw new UnexpectedValueException('DataGrid předal neplatný filtr stavu produktu.');
                    }

                    $this->status = ProductAdminStatus::from($value);
                    break;

                case 'type':
                    if (!is_string($value) || ProductAdminType::tryFrom($value) === null) {
                        throw new UnexpectedValueException('DataGrid předal neplatný filtr typu produktu.');
                    }

                    $this->type = ProductAdminType::from($value);
                    break;

                default:
                    throw new UnexpectedValueException(sprintf(
                        'Neznámý filtr administračního seznamu produktů "%s".',
                        $filter->getKey(),
                    ));
            }
        }
    }

    public function getCount(): int
    {
        return $this->productReader->findProducts(
            $this->createFilter(1, 12),
            $this->locale,
        )->totalItems;
    }

    /**
     * @return iterable<array{id: int, code: string, name: string, type: string, status: string, categories: string}>
     */
    public function getData(): iterable
    {
        $page = intdiv($this->offset, $this->limit) + 1;
        $listing = $this->productReader->findProducts(
            $this->createFilter($page, $this->limit),
            $this->locale,
        );

        $rows = [];

        foreach ($listing->items as $item) {
            if ($this->singleProductId !== null && $item->id !== $this->singleProductId) {
                continue;
            }

            $rows[] = [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name ?? '—',
                'type' => (string) $this->translator->translate('products.admin.type.' . $item->type->value),
                'status' => (string) $this->translator->translate('products.admin.status.' . $item->status->value),
                'categories' => implode(', ', $item->categoryNames),
            ];
        }

        return $rows;
    }

    public function filterOne(array $condition): self
    {
        $this->singleProductId = $this->parsePositiveInteger($condition['id'] ?? null, 'id');

        return $this;
    }

    public function limit(int $offset, int $limit): self
    {
        $this->offset = max(0, $offset);
        $this->limit = max(1, $limit);

        return $this;
    }

    public function sort(Sorting $sorting): self
    {
        // Catalog guarantees stable id ASC ordering. No sortable grid columns
        // are exposed until the public read contract supports explicit sorting.
        return $this;
    }

    private function createFilter(int $page, int $perPage): ProductAdminListFilter
    {
        return new ProductAdminListFilter(
            categoryId: $this->categoryId,
            status: $this->status,
            type: $this->type,
            page: $page,
            perPage: $perPage,
        );
    }

    private function parsePositiveInteger(mixed $value, string $field): int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            $parsed = filter_var($value, FILTER_VALIDATE_INT);

            if (is_int($parsed) && $parsed > 0) {
                return $parsed;
            }
        }

        throw new UnexpectedValueException(sprintf(
            'DataGrid předal neplatnou celočíselnou hodnotu filtru "%s".',
            $field,
        ));
    }
}
