<?php

declare(strict_types=1);

namespace App\Shared\Domain\Money;

use DomainException;

final class CurrencyMismatchException extends DomainException
{
    public static function forCurrencies(Currency $left, Currency $right): self
    {
        return new self(sprintf(
            'Cannot operate on different currencies: %s (%d minor units) and %s (%d minor units).',
            $left->code,
            $left->minorUnits,
            $right->code,
            $right->minorUnits,
        ));
    }
}
