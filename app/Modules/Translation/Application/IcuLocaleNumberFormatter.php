<?php

declare(strict_types=1);

namespace App\Modules\Translation\Application;

use App\Modules\Translation\Api\LocaleNumberFormatter;
use App\Modules\Translation\Api\NumberFormattingException;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Money\Money;
use NumberFormatter;
use Throwable;

/**
 * Formátuje čísla pomocí ICU a zachovává celočíselnou přesnost peněz.
 *
 * ICU dostává peněžní částku pouze jako celočíselnou hodnotu celé části.
 * Desetinná část se sestaví z minor units pomocí locale-aware číslic a
 * měnového oddělovače. Skutečná částka se nikdy nepřevádí na float.
 */
final class IcuLocaleNumberFormatter implements LocaleNumberFormatter
{
    public function formatNumber(int|float $number, Locale $locale): string
    {
        $formatter = $this->createFormatter($locale, NumberFormatter::DECIMAL);
        $formatted = $this->formatValue($formatter, $number);

        if ($formatted === false || $formatter->getErrorCode() > U_ZERO_ERROR) {
            throw new NumberFormattingException($locale->getCode(), $formatter->getErrorMessage());
        }

        return $formatted;
    }

    public function formatMoney(Money $money, Locale $locale): string
    {
        $formatter = $this->createFormatter($locale, NumberFormatter::CURRENCY);

        // Kód měny nastavujeme před atributy přesnosti, aby ICU načetlo
        // správné měnové vzory a symbol pro konkrétní locale.
        $this->setTextAttribute(
            $formatter,
            NumberFormatter::CURRENCY_CODE,
            $money->currency->code,
            $locale,
        );
        $this->setAttribute($formatter, NumberFormatter::MIN_FRACTION_DIGITS, 0, $locale);
        $this->setAttribute($formatter, NumberFormatter::MAX_FRACTION_DIGITS, 0, $locale);

        $minorUnits = $money->currency->minorUnits;

        if ($minorUnits === 0) {
            return $this->formatInteger($formatter, $money->minorAmount, $locale);
        }

        $factor = self::minorUnitFactor($minorUnits, $locale);
        $wholeAmount = intdiv($money->minorAmount, $factor);
        $fractionAmount = $money->minorAmount % $factor;
        $isNegative = $money->minorAmount < 0;

        // Záporný zbytek je vždy menší než faktor, takže jeho negace
        // nemůže přetéct ani u PHP_INT_MIN.
        if ($fractionAmount < 0) {
            $fractionAmount = -$fractionAmount;
        }

        $formattedWhole = $this->formatInteger($formatter, $wholeAmount, $locale);

        // U záporné částky menší než jedna hlavní jednotka je wholeAmount
        // nulové. Číselné jádro proto získáme z kladného vzoru a znaménko
        // aplikujeme až pomocí záporných měnových prefixů/suffixů.
        $sourceNegative = $wholeAmount < 0;
        $sourcePrefix = $this->getTextAttribute(
            $formatter,
            $sourceNegative ? NumberFormatter::NEGATIVE_PREFIX : NumberFormatter::POSITIVE_PREFIX,
            $locale,
        );
        $sourceSuffix = $this->getTextAttribute(
            $formatter,
            $sourceNegative ? NumberFormatter::NEGATIVE_SUFFIX : NumberFormatter::POSITIVE_SUFFIX,
            $locale,
        );

        $numberCore = substr($formattedWhole, strlen($sourcePrefix));

        if ($sourceSuffix !== '') {
            $numberCore = substr($numberCore, 0, -strlen($sourceSuffix));
        }

        $targetPrefix = $this->getTextAttribute(
            $formatter,
            $isNegative ? NumberFormatter::NEGATIVE_PREFIX : NumberFormatter::POSITIVE_PREFIX,
            $locale,
        );
        $targetSuffix = $this->getTextAttribute(
            $formatter,
            $isNegative ? NumberFormatter::NEGATIVE_SUFFIX : NumberFormatter::POSITIVE_SUFFIX,
            $locale,
        );

        $fractionFormatter = $this->createFormatter($locale, NumberFormatter::DECIMAL);
        $this->setAttribute($fractionFormatter, NumberFormatter::GROUPING_USED, 0, $locale);
        $this->setAttribute($fractionFormatter, NumberFormatter::MIN_INTEGER_DIGITS, $minorUnits, $locale);
        $this->setAttribute($fractionFormatter, NumberFormatter::MAX_INTEGER_DIGITS, $minorUnits, $locale);

        $formattedFraction = $this->formatInteger($fractionFormatter, $fractionAmount, $locale);
        $decimalSeparator = $this->getSymbolValue($formatter, NumberFormatter::MONETARY_SEPARATOR_SYMBOL);

        if ($decimalSeparator === false) {
            throw new NumberFormattingException(
                $locale->getCode(),
                $formatter->getErrorMessage(),
            );
        }

        return $targetPrefix
            . $numberCore
            . $decimalSeparator
            . $formattedFraction
            . $targetSuffix;
    }

    private function createFormatter(Locale $locale, int $style): NumberFormatter
    {
        try {
            $formatter = new NumberFormatter($locale->getCode(), $style);
        } catch (Throwable $exception) {
            throw new NumberFormattingException(
                $locale->getCode(),
                $exception->getMessage(),
                $exception,
            );
        }

        if ($formatter->getErrorCode() > U_ZERO_ERROR) {
            throw new NumberFormattingException($locale->getCode(), $formatter->getErrorMessage());
        }

        return $formatter;
    }

    private function formatInteger(
        NumberFormatter $formatter,
        int $number,
        Locale $locale,
    ): string {
        $formatted = $this->formatValue($formatter, $number, NumberFormatter::TYPE_INT64);

        if ($formatted === false || $formatter->getErrorCode() > U_ZERO_ERROR) {
            throw new NumberFormattingException($locale->getCode(), $formatter->getErrorMessage());
        }

        return $formatted;
    }

    private function setAttribute(
        NumberFormatter $formatter,
        int $attribute,
        int $value,
        Locale $locale,
    ): void {
        if (!$formatter->setAttribute($attribute, $value)) {
            throw new NumberFormattingException($locale->getCode(), $formatter->getErrorMessage());
        }
    }

    private function setTextAttribute(
        NumberFormatter $formatter,
        int $attribute,
        string $value,
        Locale $locale,
    ): void {
        if (!$formatter->setTextAttribute($attribute, $value)) {
            throw new NumberFormattingException($locale->getCode(), $formatter->getErrorMessage());
        }
    }

    private function getTextAttribute(
        NumberFormatter $formatter,
        int $attribute,
        Locale $locale,
    ): string {
        $value = $this->getTextAttributeValue($formatter, $attribute);

        if ($value === false) {
            throw new NumberFormattingException($locale->getCode(), $formatter->getErrorMessage());
        }

        return $value;
    }

    /**
     * Zachová runtime možnost selhání ICU, kterou PHPStan stub nevyjadřuje.
     *
     * @return mixed Výsledek ICU včetně případného false při chybě.
     */
    private function formatValue(
        NumberFormatter $formatter,
        int|float $number,
        ?int $type = null,
    ): mixed {
        return $type === null
            ? $formatter->format($number)
            : $formatter->format($number, $type);
    }

    /**
     * Zachová runtime možnost false, kterou PHPStan stub nevyjadřuje.
     *
     * @return mixed Textový atribut ICU nebo false při chybě.
     */
    private function getTextAttributeValue(NumberFormatter $formatter, int $attribute): mixed
    {
        return $formatter->getTextAttribute($attribute);
    }

    /**
     * Zachová runtime možnost false, kterou PHPStan stub nevyjadřuje.
     *
     * @return mixed Symbol ICU nebo false při chybě.
     */
    private function getSymbolValue(NumberFormatter $formatter, int $symbol): mixed
    {
        return $formatter->getSymbol($symbol);
    }

    private static function minorUnitFactor(int $minorUnits, Locale $locale): int
    {
        return match ($minorUnits) {
            1 => 10,
            2 => 100,
            3 => 1_000,
            4 => 10_000,
            default => throw new NumberFormattingException(
                $locale->getCode(),
                sprintf('Unsupported currency precision: %d.', $minorUnits),
            ),
        };
    }
}
