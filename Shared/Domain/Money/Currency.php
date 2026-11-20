<?php

declare(strict_types=1);

namespace App\Shared\Domain\Money;

use InvalidArgumentException;

/**
 * Neměnná definice měny potřebná pro přesné peněžní výpočty.
 *
 * Uchovává syntakticky ověřený kód a počet nejmenších desetinných jednotek.
 * Skutečnou registraci kódu a jeho přesnost ověřuje centrální katalog ISO 4217
 * v infrastruktuře; tento hodnotový objekt zůstává nezávislý na databázi.
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
            throw new InvalidArgumentException('Currency code must be three uppercase letters.');
        }

        // ISO 4217 používá pro běžné měny přesnost od nuly do čtyř míst.
        // Zda kód skutečně existuje a zda k němu přesnost patří, hlídá katalog.
        if ($this->minorUnits < 0 || $this->minorUnits > 4) {
            throw new InvalidArgumentException('Currency minor units must be between 0 and 4.');
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
