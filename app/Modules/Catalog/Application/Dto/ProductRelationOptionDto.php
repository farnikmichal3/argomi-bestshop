<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

final readonly class ProductRelationOptionDto
{
    public function __construct(
        public int $id,
        public string $code,
        public ?string $name,
    ) {}
}
