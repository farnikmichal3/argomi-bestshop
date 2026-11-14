--TEST--
Money and Currency preserve exact minor-unit arithmetic, currency identity and overflow safety
--FILE--
<?php

use App\Shared\Domain\Money\Currency;
use App\Shared\Domain\Money\CurrencyMismatchException;
use App\Shared\Domain\Money\Money;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$czk = new Currency('CZK', 2);
$eur = new Currency('EUR', 2);
$zeroDecimalCurrency = new Currency('JPY', 0);

Assert::same('CZK', $czk->code);
Assert::same(2, $czk->minorUnits);
Assert::true($czk->equals(new Currency('CZK', 2)));
Assert::false($czk->equals(new Currency('CZK', 0)));
Assert::false($czk->equals($eur));

Assert::exception(
    static fn () => new Currency('czk', 2),
    InvalidArgumentException::class,
);
Assert::exception(
    static fn () => new Currency('CZ', 2),
    InvalidArgumentException::class,
);
Assert::exception(
    static fn () => new Currency('CZK', -1),
    InvalidArgumentException::class,
);
Assert::exception(
    static fn () => new Currency('CZK', 256),
    InvalidArgumentException::class,
);

$first = new Money(1250, $czk);
$second = new Money(250, $czk);

Assert::same(1500, $first->add($second)->minorAmount);
Assert::same(1000, $first->subtract($second)->minorAmount);
Assert::same(2500, $first->multiply(2)->minorAmount);
Assert::same(-1250, $first->multiply(-1)->minorAmount);
Assert::same(0, $first->multiply(0)->minorAmount);
Assert::same(0, $first->compareTo(new Money(1250, $czk)));
Assert::same(1, $first->compareTo($second));
Assert::same(-1, $second->compareTo($first));
Assert::true($first->equals(new Money(1250, new Currency('CZK', 2))));
Assert::false($first->equals($second));
Assert::true((new Money(0, $czk))->isZero());
Assert::false($first->isZero());

// Stejný kód s jinou přesností představuje jinou měnu pro peněžní operace.
Assert::false($first->equals(new Money(1250, new Currency('CZK', 0))));
Assert::exception(
    static fn () => $first->add(new Money(1, $eur)),
    CurrencyMismatchException::class,
);
Assert::exception(
    static fn () => $first->subtract(new Money(1, $eur)),
    CurrencyMismatchException::class,
);
Assert::exception(
    static fn () => $first->compareTo(new Money(1, $eur)),
    CurrencyMismatchException::class,
);
Assert::exception(
    static fn () => $first->add(new Money(1, new Currency('CZK', 0))),
    CurrencyMismatchException::class,
);

// Nulová a mezní hodnota nesmí vyvolat falešné přetečení.
Assert::same(PHP_INT_MAX, (new Money(PHP_INT_MAX, $czk))->add(new Money(0, $czk))->minorAmount);
Assert::same(PHP_INT_MIN, (new Money(PHP_INT_MIN, $czk))->subtract(new Money(0, $czk))->minorAmount);

Assert::exception(
    static fn () => (new Money(PHP_INT_MAX, $czk))->add(new Money(1, $czk)),
    OverflowException::class,
);
Assert::exception(
    static fn () => (new Money(PHP_INT_MIN, $czk))->add(new Money(-1, $czk)),
    OverflowException::class,
);
Assert::exception(
    static fn () => (new Money(PHP_INT_MAX, $czk))->subtract(new Money(-1, $czk)),
    OverflowException::class,
);
Assert::exception(
    static fn () => (new Money(PHP_INT_MIN, $czk))->subtract(new Money(1, $czk)),
    OverflowException::class,
);
Assert::exception(
    static fn () => (new Money(PHP_INT_MIN, $czk))->multiply(-1),
    OverflowException::class,
);
Assert::exception(
    static fn () => (new Money(PHP_INT_MAX, $czk))->multiply(2),
    OverflowException::class,
);
Assert::exception(
    static fn () => (new Money(2, $czk))->multiply(PHP_INT_MIN),
    OverflowException::class,
);
Assert::exception(
    static fn () => (new Money(-2, $czk))->multiply(PHP_INT_MAX),
    OverflowException::class,
);

// Měna bez desetinných míst je platná a částka zůstává celočíselná.
Assert::same(100, (new Money(100, $zeroDecimalCurrency))->minorAmount);

echo "OK\n";
?>
--EXPECT--
OK
