<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Attribute;

use InvalidArgumentException;

/** Neměnná definice katalogového atributu. */
final readonly class Attribute
{
    public function __construct(
        public int $id,
        public string $code,
        public AttributeType $type,
        public ?int $unitId,
        public bool $isFilterable,
        public int $sortOrder,
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException('ID atributu musí být kladné.');
        }

        if ($unitId !== null && $unitId < 1) {
            throw new InvalidArgumentException('ID měrné jednotky musí být kladné.');
        }

        if (preg_match('/\A[a-z][a-z0-9_]{0,99}\z/D', $code) !== 1) {
            throw new InvalidArgumentException('Kód atributu musí obsahovat 1 až 100 malých ASCII písmen, číslic nebo podtržítek a začínat písmenem.');
        }
    }
}
