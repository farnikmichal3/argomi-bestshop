--TEST--
Quantity uses exact thousandths, rejects imprecise input and protects integer boundaries
--FILE--
<?php

declare(strict_types=1);

use App\Shared\Domain\Quantity\InvalidQuantityException;
use App\Shared\Domain\Quantity\Quantity;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

Assert::same(1250, Quantity::fromDecimal('1.25')->milliUnits);
Assert::same(1250, Quantity::fromDecimal('1.250')->milliUnits);
Assert::same(1, Quantity::fromDecimal('0.001')->milliUnits);
Assert::same(-1250, Quantity::fromDecimal('-1.25')->milliUnits);
Assert::same(0, Quantity::fromDecimal('-0.000')->milliUnits);
Assert::same(1250, Quantity::fromInteger(1)->add(Quantity::fromDecimal('0.250'))->milliUnits);

Assert::same('1.250', (new Quantity(1250))->toDecimalString());
Assert::same('0.001', (new Quantity(1))->toDecimalString());
Assert::same('-1.250', (new Quantity(-1250))->toDecimalString());
Assert::same('0.000', (new Quantity(0))->toDecimalString());
Assert::same('-9223372036854775.808', (new Quantity(PHP_INT_MIN))->toDecimalString());

// Vstup je striktní: nepřevádíme čárku, exponent, mezery ani vyšší přesnost.
foreach (['', '1,25', ' 1.25', '1.25 ', '.25', '1.', '1.0000', '1e3', '--1', '+', 'NaN'] as $invalidValue) {
    Assert::exception(
        static fn () => Quantity::fromDecimal($invalidValue),
        InvalidQuantityException::class,
    );
}

Assert::same(PHP_INT_MAX, Quantity::fromDecimal('9223372036854775.807')->milliUnits);
Assert::same(PHP_INT_MIN, Quantity::fromDecimal('-9223372036854775.808')->milliUnits);
Assert::exception(
    static fn () => Quantity::fromDecimal('9223372036854775.808'),
    OverflowException::class,
);
Assert::exception(
    static fn () => Quantity::fromDecimal('-9223372036854775.809'),
    OverflowException::class,
);

Assert::same(1500, (new Quantity(1000))->add(new Quantity(500))->milliUnits);
Assert::same(500, (new Quantity(1000))->subtract(new Quantity(500))->milliUnits);
Assert::same(-2000, (new Quantity(1000))->multiply(-2)->milliUnits);
Assert::same(0, (new Quantity(1000))->multiply(0)->milliUnits);
Assert::same(1, (new Quantity(1001))->compareTo(new Quantity(1000)));
Assert::same(-1, (new Quantity(999))->compareTo(new Quantity(1000)));
Assert::same(0, (new Quantity(1000))->compareTo(new Quantity(1000)));
Assert::true((new Quantity(1000))->equals(new Quantity(1000)));
Assert::false((new Quantity(1000))->equals(new Quantity(999)));
Assert::true((new Quantity(0))->isZero());
Assert::false((new Quantity(1))->isZero());

Assert::same(PHP_INT_MAX, (new Quantity(PHP_INT_MAX))->add(new Quantity(0))->milliUnits);
Assert::same(PHP_INT_MIN, (new Quantity(PHP_INT_MIN))->subtract(new Quantity(0))->milliUnits);
Assert::exception(
    static fn () => (new Quantity(PHP_INT_MAX))->add(new Quantity(1)),
    OverflowException::class,
);
Assert::exception(
    static fn () => (new Quantity(PHP_INT_MIN))->add(new Quantity(-1)),
    OverflowException::class,
);
Assert::exception(
    static fn () => (new Quantity(PHP_INT_MAX))->subtract(new Quantity(-1)),
    OverflowException::class,
);
Assert::exception(
    static fn () => (new Quantity(PHP_INT_MIN))->subtract(new Quantity(1)),
    OverflowException::class,
);
Assert::exception(
    static fn () => (new Quantity(PHP_INT_MIN))->multiply(-1),
    OverflowException::class,
);
Assert::exception(
    static fn () => (new Quantity(2))->multiply(PHP_INT_MIN),
    OverflowException::class,
);
Assert::exception(
    static fn () => (new Quantity(-2))->multiply(PHP_INT_MAX),
    OverflowException::class,
);
Assert::exception(
    static fn () => Quantity::fromInteger(intdiv(PHP_INT_MAX, 1000) + 1),
    OverflowException::class,
);
Assert::exception(
    static fn () => Quantity::fromInteger(intdiv(PHP_INT_MIN, 1000) - 1),
    OverflowException::class,
);

echo "OK\n";
?>
--EXPECT--
OK
