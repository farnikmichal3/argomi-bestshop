<?php

declare(strict_types=1);

namespace App\Shared\Domain\PublicId;

use Stringable;

/**
 * Neměnný veřejný identifikátor ve standardním textovém zápisu ULID.
 *
 * Doména uchovává pouze kanonickou podobu identifikátoru a nezná knihovnu,
 * která ULID generuje. Díky tomu lze generátor v infrastruktuře později vyměnit.
 */
final readonly class PublicId implements Stringable
{
    private function __construct(
        private string $value,
    ) {}

    /**
     * Vytvoří veřejné ID z kanonického 26znakového zápisu ULID.
     *
     * První znak omezuje rozsah 128bitové hodnoty; abeceda vylučuje
     * nejednoznačné znaky I, L, O a U podle Crockford Base32.
     */
    public static function fromString(string $value): self
    {
        if (preg_match('/\A[0-7][0-9A-HJKMNP-TV-Z]{25}\z/', $value) !== 1) {
            throw new InvalidPublicIdException('Veřejné ID musí být platný ULID v kanonickém zápisu.');
        }

        return new self($value);
    }

    /**
     * Vrátí kanonický 26znakový zápis ULID.
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Porovná identifikátory podle jejich kanonické hodnoty.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Umožní bezpečné použití identifikátoru v textovém kontextu.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
