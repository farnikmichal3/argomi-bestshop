<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Api\\Dto;

use App\\Modules\\Catalog\\Domain\\Product\\ProductStatus;
use App\\Modules\\Catalog\\Domain\\Product\\ProductType;

/**
 * Veřejná položka administrátorského výpisu produktu.
 */
final readonly class ProductAdminListItem
{
    /**
     * @param list<string> $categoryNames
     */
    public function __construct(
        public int $id,
        public string $code,
        public ?string $name,
        public ProductType $type,
        public ProductStatus $status,
        public array $categoryNames,
    ) {}
}
