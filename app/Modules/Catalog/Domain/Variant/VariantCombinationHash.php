<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Variant;

use InvalidArgumentException;

/**
 * Vytváří stabilní SHA-256 otisk kombinace atributů produktové varianty.
 *
 * Kanonický vstup je nezávislý na pořadí předaných atributů. Kódy atributů
 * se řadí binárně; hodnoty se před spojením bezpečně escapují.
 */
final class VariantCombinationHash
{
    /**
     * @param array<string, array{type: 'enum'|'number'|'text'|'bool', value: string|bool}> $attributes
     */
    public static function create(array $attributes): string
    {
        return hash('sha256', self::canonicalize($attributes));
    }

    /**
     * @param array<string, array{type: 'enum'|'number'|'text'|'bool', value: string|bool}> $attributes
     */
    public static function canonicalize(array $attributes): string
    {
        if ($attributes === []) {
            throw new InvalidArgumentException('Kombinace varianty musí obsahovat alespoň jeden atribut.');
        }

        // strcmp zajišťuje stejné pořadí bez závislosti na locale systému.
        uksort($attributes, 'strcmp');

        $parts = [];

        foreach ($attributes as $code => $attribute) {
            if (!preg_match('/^[a-z][a-z0-9_]*$/D', $code)) {
                throw new InvalidArgumentException('Kód atributu musí být malé ASCII písmeno následované malými písmeny, číslicemi nebo podtržítkem.');
            }

            $type = $attribute['type'] ?? null;
            $value = $attribute['value'] ?? null;

            if (!is_string($type) || !in_array($type, ['enum', 'number', 'text', 'bool'], true)) {
                throw new InvalidArgumentException(sprintf('Atribut %s má nepodporovaný datový typ.', $code));
            }

            $canonicalValue = match ($type) {
                'enum' => self::canonicalizeStringValue($value, $code),
                'number' => self::canonicalizeNumber($value, $code),
                'text' => self::canonicalizeStringValue($value, $code),
                'bool' => self::canonicalizeBoolean($value, $code),
            };

            $parts[] = $code . '=' . self::escape($canonicalValue);
        }

        return implode(';', $parts);
    }

    private static function canonicalizeStringValue(mixed $value, string $code): string
    {
        if (!is_string($value) || $value === '' || preg_match('//u', $value) !== 1) {
            throw new InvalidArgumentException(sprintf('Textová hodnota atributu %s musí být neprázdný platný UTF-8 řetězec.', $code));
        }

        return $value;
    }

    /**
     * Číselné hodnoty odpovídají přesnosti DECIMAL(20,6); float je zakázán.
     */
    private static function canonicalizeNumber(mixed $value, string $code): string
    {
        if (
            !is_string($value)
            || !preg_match('/^-?(?:0|[1-9][0-9]{0,13})(?:\.[0-9]{1,6})?$/D', $value)
        ) {
            throw new InvalidArgumentException(sprintf('Číselná hodnota atributu %s musí být desetinný řetězec s nejvýše šesti místy.', $code));
        }

        $negative = str_starts_with($value, '-');
        $unsigned = $negative ? substr($value, 1) : $value;
        [$integer, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');

        $fraction = rtrim($fraction, '0');
        $normalized = $integer . ($fraction === '' ? '' : '.' . $fraction);

        // Záporná nula má stejnou identitu jako běžná nula.
        return $negative && $normalized !== '0' ? '-' . $normalized : $normalized;
    }

    private static function canonicalizeBoolean(mixed $value, string $code): string
    {
        if (!is_bool($value)) {
            throw new InvalidArgumentException(sprintf('Logická hodnota atributu %s musí být typu bool.', $code));
        }

        return $value ? '1' : '0';
    }

    private static function escape(string $value): string
    {
        return strtr($value, [
            '%' => '%25',
            ';' => '%3B',
            '=' => '%3D',
        ]);
    }
}
