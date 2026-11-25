<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

/** @param array<string,string> $translations */
final readonly class ProductCustomerOptionValueData
{
    public function __construct(
        public int $id,
        public string $code,
        public int $sortOrder,
        public array $translations,
    ) {}
}
