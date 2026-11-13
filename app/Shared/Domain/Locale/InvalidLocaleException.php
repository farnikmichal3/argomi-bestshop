<?php

declare(strict_types=1);

namespace App\Shared\Domain\Locale;

use DomainException;

/**
 * Označuje jazykový identifikátor mimo podporovanou podmnožinu BCP 47.
 */
final class InvalidLocaleException extends DomainException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('Neplatný jazykový identifikátor: "%s".', $value));
    }
}
