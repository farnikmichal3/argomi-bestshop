<?php

declare(strict_types=1);

namespace App\Shared\Domain\Percentage;

use App\Shared\Domain\Exception\DomainException;

/**
 * Označuje procentní vstup, který nedodržuje přesný formát hodnoty Percentage.
 *
 * Rozsah sazby není důvodem k této výjimce: záporné sazby i sazby nad 100 %
 * jsou platné. Překročení rozsahu PHP integeru se hlásí jako OverflowException.
 */
final class InvalidPercentageException extends DomainException
{
    /**
     * Vytvoří výjimku pro hodnotu, kterou nelze bezpečně převést na Percentage.
     */
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid percentage value: "%s".', $value));
    }
}
