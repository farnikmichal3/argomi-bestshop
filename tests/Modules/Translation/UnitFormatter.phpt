--TEST--
ICU unit formatter selects localized plural names and preserves Quantity decimal input
--FILE--
<?php

use App\Modules\Translation\Api\UnitFormattingException;
use App\Modules\Translation\Application\IcuUnitFormatter;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Quantity\Quantity;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$formatter = new IcuUnitFormatter();
$cs = Locale::fromString('cs');
$en = Locale::fromString('en');

$csPattern = '{count, plural, one {kus} few {kusy} many {kusu} other {kusů}}';
$enPattern = '{count, plural, one {piece} other {pieces}}';

Assert::same('kus', $formatter->formatName($csPattern, Quantity::fromInteger(1), $cs));
Assert::same('kusy', $formatter->formatName($csPattern, Quantity::fromInteger(2), $cs));
Assert::same('kusů', $formatter->formatName($csPattern, Quantity::fromInteger(5), $cs));
Assert::same('kusu', $formatter->formatName($csPattern, Quantity::fromDecimal('1.250'), $cs));
Assert::same('kusu', $formatter->formatName($csPattern, Quantity::fromDecimal('0.001'), $cs));

Assert::same('piece', $formatter->formatName($enPattern, Quantity::fromInteger(1), $en));
Assert::same('pieces', $formatter->formatName($enPattern, Quantity::fromInteger(2), $en));
Assert::same('pieces', $formatter->formatName($enPattern, Quantity::fromDecimal('1.250'), $en));
Assert::same('kus', $formatter->formatName($csPattern, Quantity::fromDecimal('1.000'), $cs));

Assert::same('ks', $formatter->formatSymbol('ks'));
Assert::same('m²', $formatter->formatSymbol('m²'));

// Nejvyšší podporované celé číslo i desetinné množství těsně pod 2^41.
Assert::same(
    'kusů',
    $formatter->formatName($csPattern, new Quantity(2199023255551000), $cs),
);
Assert::same(
    'kusu',
    $formatter->formatName($csPattern, new Quantity(2199023255551999), $cs),
);
Assert::same(
    'kusu',
    $formatter->formatName($csPattern, new Quantity(-2199023255551999), $cs),
);

// Za hranicí bezpečné přesnosti ICU se chyba propaguje místo chybného plurálu.
Assert::exception(
    static fn () => $formatter->formatName($csPattern, new Quantity(2199023255552000), $cs),
    UnitFormattingException::class,
);
Assert::exception(
    static fn () => $formatter->formatName($csPattern, new Quantity(-2199023255552000), $cs),
    UnitFormattingException::class,
);
Assert::exception(
    static fn () => $formatter->formatName($csPattern, new Quantity(PHP_INT_MIN), $cs),
    UnitFormattingException::class,
);
Assert::exception(
    static fn () => $formatter->formatName($csPattern, new Quantity(PHP_INT_MAX), $cs),
    UnitFormattingException::class,
);

Assert::exception(
    static fn () => $formatter->formatName('{count, plural, one {kus}', Quantity::fromInteger(1), $cs),
    UnitFormattingException::class,
);

echo "OK\n";
?>
--EXPECT--
OK
