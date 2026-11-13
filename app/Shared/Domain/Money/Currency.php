<?php

declare(strict_types=1);

namespace App\Shared\Domain\Money;

use InvalidArgumentException;

/**
 * Neměnná definice měny potřebná pro přesné peněžní výpočty.
 *
 * Uchovává pouze stabilní ISO kód a počet nejmenších desetinných jednotek.
 * Název a symbol měny patří do prezentační/konfigurační vrstvy, nikoliv do VO.
 */
final readonly class Currency
{
    public function __construct(
        public string $code,
        public int $minorUnits,
    ) {
        // Třípísmenný kód držíme ve standardním velkém zápisu, aby šel bezpečně porovnávat.
        if (preg_match('/\A[A-Z]{3}\z/', $this->code) !== 1) {
            throw new InvalidArgumentException('Currency code must be a three-letter uppercase ISO code.');
        }

        // Databázový sloupec TINYINT UNSIGNED určuje rozsah 0 až 255.
        if ($this->minorUnits < 0 || $this->minorUnits > 255) {
            throw new InvalidArgumentException('Currency minor units must be between 0 and 255.');
        }
    }

    /**
     * Porovná měnu podle jejího kódu i přesnosti.
     *
     * Samotný kód nestačí: stejný kód s odlišnou přesností by změnil význam částky.
     */
    public function equals(self $other): bool
    {
        return $this->code === $other->code
            && $this->minorUnits === $other->minorUnits;
    }
}
