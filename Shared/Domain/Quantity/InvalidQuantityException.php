<?php

declare(strict_types=1);

namespace App\Shared\Domain\Quantity;

use App\Shared\Domain\Exception\DomainException;

/**
 * Označuje neplatný desetinný zápis množství.
 *
 * Výjimka se používá pro vstupy, které nedodržují přesný formát Quantity,
 * například obsahují více než tři desetinná místa nebo nepovolený oddělovač.
 */
final class InvalidQuantityException extends DomainException
{
    /**
     * Vytvoří výjimku pro hodnotu, kterou nelze bezpečně převést na Quantity.
     */
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid quantity decimal value: "%s".', $value));
    }
}
