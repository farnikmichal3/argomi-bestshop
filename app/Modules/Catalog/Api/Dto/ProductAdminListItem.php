<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

use App\Modules\Catalog\Api\ProductAdminStatus;
use App\Modules\Catalog\Api\ProductAdminType;

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
        public ProductAdminType $type,
        public ProductAdminStatus $status,
        public array $categoryNames,
    ) {}
}
