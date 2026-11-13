<?php

declare(strict_types=1);

namespace App\Shared\Domain\Money;

use DomainException;

/**
 * Označuje pokus o peněžní operaci mezi rozdílnými měnami.
 *
 * Výjimka zahrnuje také rozdílný počet desetinných míst, protože ten
 * ovlivňuje interpretaci celočíselné částky v nejmenších jednotkách.
 */
final class CurrencyMismatchException extends DomainException
{
    /**
     * Vytvoří výjimku s popisem obou měn, které nelze společně použít.
     */
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
