--TEST--
Locale number formatter respects locale and preserves exact Money minor units
--FILE--
<?php

use App\Modules\Translation\Application\IcuLocaleNumberFormatter;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Money\Currency;
use App\Shared\Domain\Money\Money;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$formatter = new IcuLocaleNumberFormatter();
$normalizeSpaces = static fn (string $value): string => str_replace(
    ["\u{00A0}", "\u{202F}"],
    ' ',
    $value,
);

Assert::same(
    '12 345,67',
    $normalizeSpaces($formatter->formatNumber(12345.67, Locale::fromString('cs'))),
);
Assert::same(
    '12,345.67',
    $normalizeSpaces($formatter->formatNumber(12345.67, Locale::fromString('en'))),
);
Assert::same(
    '12.345,67',
    $normalizeSpaces($formatter->formatNumber(12345.67, Locale::fromString('de'))),
);

$czk = $formatter->formatMoney(
    new Money(123456, new Currency('CZK', 2)),
    Locale::fromString('cs'),
);
Assert::contains('1 234,56', $normalizeSpaces($czk));
Assert::contains('Kč', $czk);

$negativeCzk = $formatter->formatMoney(
    new Money(-123456, new Currency('CZK', 2)),
    Locale::fromString('cs'),
);
Assert::contains('1 234,56', $normalizeSpaces($negativeCzk));
Assert::notSame($czk, $negativeCzk);

$eur = $formatter->formatMoney(
    new Money(123456, new Currency('EUR', 2)),
    Locale::fromString('en'),
);
Assert::contains('1,234.56', $normalizeSpaces($eur));
Assert::contains('€', $eur);

$zeroDecimalCurrency = $formatter->formatMoney(
    new Money(1234, new Currency('JPY', 0)),
    Locale::fromString('en'),
);
Assert::contains('1,234', $normalizeSpaces($zeroDecimalCurrency));
Assert::false(str_contains($zeroDecimalCurrency, '.'));

$fourDecimalCurrency = $formatter->formatMoney(
    new Money(12345678, new Currency('CLF', 4)),
    Locale::fromString('en'),
);
Assert::contains('1,234.5678', $normalizeSpaces($fourDecimalCurrency));

$maximum = $formatter->formatMoney(
    new Money(PHP_INT_MAX, new Currency('EUR', 2)),
    Locale::fromString('en'),
);
Assert::contains('.07', $normalizeSpaces($maximum));

$minimum = $formatter->formatMoney(
    new Money(PHP_INT_MIN, new Currency('EUR', 2)),
    Locale::fromString('en'),
);
Assert::contains('.08', $normalizeSpaces($minimum));
Assert::notSame($maximum, $minimum);

echo "OK\n";
?>
--EXPECT--
OK
