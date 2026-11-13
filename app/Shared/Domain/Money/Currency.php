<?php

declare(strict_types=1);

namespace App\Shared\Domain\Money;

use InvalidArgumentException;

final readonly class Currency
{
    public function __construct(
        public string $code,
        public int $minorUnits,
    ) {
        if (preg_match('/\A[A-Z]{3}\z/', $this->code) !== 1) {
            throw new InvalidArgumentException('Currency code must be a three-letter uppercase ISO code.');
        }

        if ($this->minorUnits < 0 || $this->minorUnits > 255) {
            throw new InvalidArgumentException('Currency minor units must be between 0 and 255.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code
            && $this->minorUnits === $other->minorUnits;
    }
}
