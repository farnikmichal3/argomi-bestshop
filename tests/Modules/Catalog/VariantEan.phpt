--TEST--
Variant EAN accepts valid EAN-8 and EAN-13 and rejects invalid values
--FILE--
<?php

use App\Modules\Catalog\Domain\Variant\VariantEan;
use Tester\Assert;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';

Assert::same('4006381333931', VariantEan::fromString('4006381333931')->value);
Assert::same('96385074', VariantEan::fromString('96385074')->value);
Assert::exception(
    fn () => VariantEan::fromString('4006381333932'),
    InvalidArgumentException::class,
);
Assert::exception(
    fn () => VariantEan::fromString('400638133393'),
    InvalidArgumentException::class,
);
Assert::exception(
    fn () => VariantEan::fromString('40063813A3931'),
    InvalidArgumentException::class,
);

echo "OK\n";
?>
--EXPECT--
OK
