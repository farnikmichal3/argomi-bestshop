<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Api\Dto\ProductCustomerOptionConfigurationData;
use App\Modules\Catalog\Api\Dto\ProductCustomerOptionData;
use App\Modules\Catalog\Api\Dto\ProductCustomerOptionValueData;
use App\Modules\Catalog\Api\ProductCustomerOptionReader;

final readonly class ProductCustomerOptionQueryService implements ProductCustomerOptionReader
{
    public function __construct(private ProductCustomerOptionRepository $repository) {}

    public function findForProduct(int $productId): ?ProductCustomerOptionConfigurationData
    {
        if ($productId < 1 || !$this->repository->productExists($productId)) {
            return null;
        }

        $options = [];
        foreach ($this->repository->findForProduct($productId) as $option) {
            $values = [];
            foreach ($option['values'] as $value) {
                $values[] = new ProductCustomerOptionValueData(
                    $value['id'],
                    $value['code'],
                    $value['sortOrder'],
                    $value['translations'],
                );
            }

            $options[] = new ProductCustomerOptionData(
                $option['id'],
                $option['code'],
                $option['type'],
                $option['isRequired'],
                $option['sortOrder'],
                $option['translations'],
                $values,
            );
        }

        return new ProductCustomerOptionConfigurationData($options);
    }
}
