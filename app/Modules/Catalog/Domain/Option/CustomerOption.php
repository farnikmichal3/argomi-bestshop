<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Option;

use InvalidArgumentException;

/** Neměnná definice zákaznické volby při konfiguraci produktu. */
final readonly class CustomerOption
{
    public function __construct(
        public int $id,
        public int $productId,
        public string $code,
        public CustomerOptionType $type,
        public bool $isRequired,
        public int $sortOrder,
    ) {
        if ($id < 1 || $productId < 1) {
            throw new InvalidArgumentException('ID volby i produktu musí být kladná.');
        }

        if (preg_match('/\A[a-z][a-z0-9_]{0,99}\z/D', $code) !== 1) {
            throw new InvalidArgumentException('Kód zákaznické volby musí obsahovat 1 až 100 malých ASCII písmen, číslic nebo podtržítek a začínat písmenem.');
        }
    }
}
