<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

final readonly class ProductRelationOption
{
    public function __construct(
        public int $id,
        public string $code,
        public ?string $name,
    ) {}
}
