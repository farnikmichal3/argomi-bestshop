<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Variant;

use InvalidArgumentException;

final readonly class VariantEan
{
    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        if (preg_match('/\A(?:[0-9]{8}|[0-9]{13})\z/D', $value) !== 1) {
            throw new InvalidArgumentException('EAN musí obsahovat přesně 8 nebo 13 číslic.');
        }

        $body = substr($value, 0, -1);
        $providedCheckDigit = (int) substr($value, -1);
        $sum = 0;
        $multiplier = 3;

        for ($index = strlen($body) - 1; $index >= 0; $index--) {
            $sum += (int) $body[$index] * $multiplier;
            $multiplier = $multiplier === 3 ? 1 : 3;
        }

        $expectedCheckDigit = (10 - ($sum % 10)) % 10;
        if ($providedCheckDigit !== $expectedCheckDigit) {
            throw new InvalidArgumentException('Kontrolní číslice EAN není platná.');
        }

        return new self($value);
    }
}
