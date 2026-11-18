<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

/**
 * @param list<CustomerOptionValueDto> $values
 */
final readonly class CustomerOptionDetailDto
{
    public function __construct(
        public int $id,
        public string $code,
        public string $type,
        public bool $isRequired,
        public int $sortOrder,
        public ?string $name,
        public array $values,
    ) {}
}
