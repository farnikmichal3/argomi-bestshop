<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

use App\Modules\Catalog\Domain\Option\CustomerOption;

/**
 * Definice zákaznické volby včetně ID povolených hodnot.
 *
 * @param list<int> $valueIds
 */
final readonly class CustomerOptionDefinitionDto
{
    public function __construct(
        public CustomerOption $option,
        public array $valueIds,
    ) {}
}
