<?php

declare(strict_types=1);

namespace App\Modules\Tax\Api\Dto;

/**
 * Minimal public tax-class option for administrative selectors.
 */
final readonly class TaxClassOption
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
    ) {}
}
