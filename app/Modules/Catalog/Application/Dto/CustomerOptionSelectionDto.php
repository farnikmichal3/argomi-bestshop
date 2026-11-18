<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

/**
 * Normalizovaný výběr zákaznických voleb pro konkrétní produkt.
 *
 * @param list<ResolvedCustomerOptionDto> $options
 */
final readonly class CustomerOptionSelectionDto
{
    /** @param list<ResolvedCustomerOptionDto> $options */
    public function __construct(
        public int $productId,
        public array $options,
    ) {}
}
