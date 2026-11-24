<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Api\AttributeDataType;
use App\Modules\Catalog\Api\Dto\ProductEditorAttributeOption;
use App\Modules\Catalog\Api\Dto\ProductEditorAttributeValueOption;
use App\Modules\Catalog\Api\Dto\ProductEditorConfigurationData;
use App\Modules\Catalog\Api\ProductEditorConfigurationReader;
use App\Shared\Domain\Locale\Locale;
use UnexpectedValueException;

final readonly class ProductEditorConfigurationQueryService implements ProductEditorConfigurationReader
{
    public function __construct(private ProductEditorConfigurationRepository $repository) {}

    public function findForProduct(int $productId, Locale $locale): ?ProductEditorConfigurationData
    {
        if ($productId < 1 || !$this->repository->productExists($productId)) {
            return null;
        }

        $attributes = [];
        foreach ($this->repository->findAvailableAttributes($locale) as $row) {
            $values = [];
            foreach ($row['values'] as $value) {
                $values[] = new ProductEditorAttributeValueOption(
                    $value['id'],
                    $value['code'],
                    $value['label'] ?? $value['code'],
                );
            }

            $attributes[] = new ProductEditorAttributeOption(
                $row['id'],
                $row['code'],
                AttributeDataType::from($row['type']),
                $row['name'] ?? $row['code'],
                $row['unitName'],
                $row['unitSymbol'],
                $values,
            );
        }

        return new ProductEditorConfigurationData(
            $attributes,
            $this->repository->findAssignedValues($productId),
            $this->repository->findVariantAxisIds($productId),
        );
    }
}
