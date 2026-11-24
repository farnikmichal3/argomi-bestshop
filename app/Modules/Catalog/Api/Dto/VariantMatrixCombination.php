<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

final readonly class VariantMatrixCombination
{
    /**
     * @param array<string, array{type: string, value: string|bool}> $values
     */
    public function __construct(
        public string $hash,
        public array $values,
        public string $label,
        public ?int $variantId,
        public ?string $sku,
        public ?bool $isActive,
        public ?bool $isDefault,
    ) {}

    public function exists(): bool
    {
        return $this->variantId !== null;
    }
}
