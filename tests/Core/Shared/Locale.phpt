--TEST--
Locale normalizes supported language tags and rejects unsupported syntax
--FILE--
<?php

use App\Shared\Domain\Locale\InvalidLocaleException;
use App\Shared\Domain\Locale\Locale;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$cs = Locale::fromString('cs');
$en = Locale::fromString('EN');
$usEnglish = Locale::fromString('en-us');
$traditionalChinese = Locale::fromString('ZH-hANT-tw');
$serbianLatin = Locale::fromString('sr-Latn');
$numericRegion = Locale::fromString('es-419');

Assert::same('cs', $cs->getCode());
Assert::same('en', $en->getCode());
Assert::same('en-US', $usEnglish->getCode());
Assert::same('zh-Hant-TW', $traditionalChinese->getCode());
Assert::same('sr-Latn', $serbianLatin->getCode());
Assert::same('es-419', $numericRegion->getCode());

Assert::same('en', $usEnglish->getLanguage());
Assert::same('zh', $traditionalChinese->getLanguage());
Assert::same('sr', $serbianLatin->getLanguage());
Assert::same('es', $numericRegion->getLanguage());

Assert::true(Locale::fromString('cs')->equals($cs));
Assert::false($cs->equals($en));
Assert::same('zh-Hant-TW', (string) $traditionalChinese);

foreach (['', 'c', 'abcd', 'en_US', 'en--US', 'en-US-extra', 'en-u-ca-gregory', '123', 'en-', 'en-US-u-ca'] as $invalidValue) {
    Assert::exception(
        static fn () => Locale::fromString($invalidValue),
        InvalidLocaleException::class,
    );
}

echo "OK\n";
?>
--EXPECT--
OK
