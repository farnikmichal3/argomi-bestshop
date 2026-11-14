--TEST--
PublicId validates canonical ULIDs and Symfony generator returns distinct valid identifiers
--FILE--
<?php

declare(strict_types=1);

use App\Shared\Domain\PublicId\InvalidPublicIdException;
use App\Shared\Domain\PublicId\PublicId;
use App\Shared\Domain\PublicId\PublicIdGenerator;
use App\Shared\Infrastructure\PublicId\SymfonyUlidGenerator;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$validValue = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
$first = PublicId::fromString($validValue);
$second = PublicId::fromString($validValue);

Assert::same($validValue, $first->toString());
Assert::same($validValue, (string) $first);
Assert::true($first->equals($second));
Assert::false($first->equals(PublicId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAW')));

foreach ([
    '',
    '01ARZ3NDEKTSV4RRFFQ69G5FA',
    '01ARZ3NDEKTSV4RRFFQ69G5FAVX',
    '81ARZ3NDEKTSV4RRFFQ69G5FAV',
    '01ARZ3NDEKTSV4RRFFQ69G5FAI',
    '01ARZ3NDEKTSV4RRFFQ69G5FAL',
    '01ARZ3NDEKTSV4RRFFQ69G5FAO',
    '01ARZ3NDEKTSV4RRFFQ69G5FAU',
    '01arz3ndektsv4rrffq69g5fav',
    ' 01ARZ3NDEKTSV4RRFFQ69G5FAV',
] as $invalidValue) {
    Assert::exception(
        static fn () => PublicId::fromString($invalidValue),
        InvalidPublicIdException::class,
    );
}

$generator = new SymfonyUlidGenerator();
Assert::type(PublicIdGenerator::class, $generator);

$generatedValues = [];

for ($index = 0; $index < 20; $index++) {
    $generated = $generator->generate();
    $value = $generated->toString();

    Assert::same(26, strlen($value));
    Assert::true(PublicId::fromString($value)->equals($generated));
    $generatedValues[$value] = true;
}

Assert::count(20, $generatedValues);

echo "OK\n";
--EXPECT--
OK
