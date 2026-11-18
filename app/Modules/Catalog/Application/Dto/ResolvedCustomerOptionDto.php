<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

use App\Modules\Catalog\Domain\Option\CustomerOptionType;

/** Normalizovaný výběr jedné zákaznické volby bez cenových údajů. */
final readonly class ResolvedCustomerOptionDto
{
    public function __construct(
        public int $optionId,
        public CustomerOptionType $type,
        public ?int $valueId,
        public ?string $textValue,
    ) {}
}
