<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

/**
 * @param array<string,string> $translations
 * @param list<ProductCustomerOptionValueData> $values
 */
final readonly class ProductCustomerOptionData
{
    public function __construct(
        public int $id,
        public string $code,
        public string $type,
        public bool $isRequired,
        public int $sortOrder,
        public array $translations,
        public array $values,
    ) {}
}
