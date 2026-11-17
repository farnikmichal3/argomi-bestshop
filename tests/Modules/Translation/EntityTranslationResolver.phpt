--TEST--
Entity translation resolver prefers requested locale and falls back to the default locale
--FILE--
<?php

use App\\Modules\\Translation\\Application\\EntityTranslationResolver;
use App\\Shared\\Domain\\Locale\\Locale;
use Tester\\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$resolver = new EntityTranslationResolver();
$requestedLocale = Locale::fromString('en');
$defaultLocale = Locale::fromString('cs');
$requestedTranslation = new stdClass();
$defaultTranslation = new stdClass();

Assert::same(
    $requestedTranslation,
    $resolver->resolve(
        $requestedLocale,
        $defaultLocale,
        $requestedTranslation,
        $defaultTranslation,
    ),
    'The requested locale must have priority over the default locale.',
);

Assert::same(
    $defaultTranslation,
    $resolver->resolve(
        $requestedLocale,
        $defaultLocale,
        null,
        $defaultTranslation,
    ),
    'A missing requested translation must fall back to the default locale.',
);

Assert::null(
    $resolver->resolve(
        $requestedLocale,
        $defaultLocale,
        null,
        null,
    ),
    'Missing translations in both locales must remain null.',
);

Assert::same(
    $requestedTranslation,
    $resolver->resolve(
        $defaultLocale,
        $defaultLocale,
        $requestedTranslation,
        $defaultTranslation,
    ),
    'When locales are equal, the requested translation is the only lookup result.',
);

Assert::null(
    $resolver->resolve(
        $defaultLocale,
        $defaultLocale,
        null,
        $defaultTranslation,
    ),
    'When locales are equal, a second value must not simulate a duplicate fallback lookup.',
);

echo "OK\\n";
?>
--EXPECT--
OK
