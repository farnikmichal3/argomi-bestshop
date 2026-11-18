--TEST--
Database translator formats ICU messages with explicit locale and typed arguments
--FILE--
<?php

use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Modules\Translation\Infrastructure\Diagnostics\MissingTranslationDiagnostics;
use App\Modules\Translation\Infrastructure\Persistence\DatabaseMessageStore;
use App\Modules\Translation\Infrastructure\Translation\DatabaseTranslatorFactory;
use App\Modules\Translation\Infrastructure\Translation\MessageFormattingException;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Infrastructure\Persistence\Locale\DatabaseLocaleProvider;
use Nette\Localization\Translator;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
$container = $integration->container;
$pdo = $integration->database->getConnection()->getPdo();

/** @var TranslatorFactory $diFactory */
$diFactory = $container->getByType(TranslatorFactory::class);
Assert::type(TranslatorFactory::class, $diFactory);

// Přímé DB úložiště drží test nezávislý na sdílené persistentní cache
// a na pořadí paralelně spouštěných integračních testů.
$factory = new DatabaseTranslatorFactory(
    new DatabaseMessageStore($integration->database),
    new DatabaseLocaleProvider($integration->database),
    new MissingTranslationDiagnostics(new NullLogger()),
);

$token = bin2hex(random_bytes(8));
$messageKey = 'f4_05.shared_key.' . $token;
$missingKey = 'f4_05.missing_key.' . $token;
$namedKey = 'f4_05.named.' . $token;
$positionalKey = 'f4_05.positional.' . $token;
$pluralCsKey = 'f4_05.plural_cs.' . $token;
$pluralEnKey = 'f4_05.plural_en.' . $token;
$malformedKey = 'f4_05.malformed.' . $token;
$fallbackOnlyKey = 'f4_06.fallback_only.' . $token;
$fallbackPluralKey = 'f4_06.fallback_plural.' . $token;
$domainIsolationKey = 'f4_06.domain_isolation.' . $token;

$pdo->beginTransaction();

try {
    $rows = [
        ['catalog_message_translation', $messageKey, 'cs', 'Katalog česky'],
        ['catalog_message_translation', $messageKey, 'en', 'Catalog in English'],
        ['cart_message_translation', $messageKey, 'cs', 'Košík česky'],
        ['catalog_message_translation', $namedKey, 'cs', 'Ahoj, {name}!'],
        ['catalog_message_translation', $positionalKey, 'cs', '{0} má {1} položek'],
        ['catalog_message_translation', $pluralCsKey, 'cs', '{count, plural, one{# položka} few{# položky} other{# položek}}'],
        ['catalog_message_translation', $pluralEnKey, 'en', '{count, plural, one{# item} other{# items}}'],
        ['catalog_message_translation', $malformedKey, 'cs', '{count, plural, one{jedna}'],
        ['catalog_message_translation', $fallbackOnlyKey, 'cs', 'Text pouze ve výchozím jazyce'],
        ['catalog_message_translation', $fallbackPluralKey, 'cs', '{count, plural, one{# položka} few{# položky} other{# položek}}'],
        ['cart_message_translation', $domainIsolationKey, 'cs', 'Jiná doména'],
    ];

    foreach ($rows as [$table, $key, $locale, $message]) {
        $statement = $pdo->prepare(sprintf(
            'INSERT INTO %s (message_key, locale, message_text) VALUES (?, ?, ?)',
            $table,
        ));
        $statement->execute([$key, $locale, $message]);
    }

    $catalogCs = $factory->createTranslator(TranslationDomain::Catalog, Locale::fromString('cs'));
    $catalogEn = $factory->createTranslator(TranslationDomain::Catalog, Locale::fromString('en'));
    $cartCs = $factory->createTranslator(TranslationDomain::Cart, Locale::fromString('cs'));
    $catalogEnWithFallback = $factory->createTranslator(TranslationDomain::Catalog, Locale::fromString('en'));
    $cartEnWithFallback = $factory->createTranslator(TranslationDomain::Cart, Locale::fromString('en'));

    Assert::type(Translator::class, $catalogCs);
    Assert::same('Katalog česky', $catalogCs->translate($messageKey));
    Assert::same('Catalog in English', $catalogEn->translate($messageKey));
    Assert::same('Košík česky', $cartCs->translate($messageKey));

    // Požadovaný jazyk má přednost; chybějící klíč použije výchozí jazyk.
    Assert::same($missingKey, $catalogCs->translate($missingKey));
    Assert::same('Text pouze ve výchozím jazyce', $catalogEnWithFallback->translate($fallbackOnlyKey));
    Assert::same('1 položka', $catalogEnWithFallback->translate($fallbackPluralKey, ['count' => 1]));
    Assert::same('2 položky', $catalogEnWithFallback->translate($fallbackPluralKey, ['count' => 2]));
    Assert::same($domainIsolationKey, $catalogEnWithFallback->translate($domainIsolationKey));
    Assert::same('Jiná doména', $cartEnWithFallback->translate($domainIsolationKey));

    // Bez parametrů zůstává i ICU vzor beze změny.
    Assert::same('Ahoj, {name}!', $catalogCs->translate($namedKey));
    Assert::same('Ahoj, Michale!', $catalogCs->translate($namedKey, ['name' => 'Michale']));
    Assert::same('Košík má 3 položek', $catalogCs->translate($positionalKey, 'Košík', 3));

    // Plurály se řídí locale konkrétního katalogu, nikoliv globálním jazykem.
    Assert::same('2 položky', $catalogCs->translate($pluralCsKey, ['count' => 2]));
    Assert::same('2 items', $catalogEn->translate($pluralEnKey, ['count' => 2]));
    Assert::same('1 item', $catalogEn->translate($pluralEnKey, ['count' => 1]));

    Assert::exception(
        static fn () => $catalogCs->translate($malformedKey, ['count' => 2]),
        MessageFormattingException::class,
    );

    Assert::exception(
        static fn () => $catalogCs->translate($namedKey, ['name' => new stdClass()]),
        InvalidArgumentException::class,
    );

    $stringableKeyObject = new class($messageKey) implements Stringable {
        public function __construct(
            private string $value,
        ) {}

        public function __toString(): string
        {
            return $this->value;
        }
    };

    $stringableParameter = new class implements Stringable {
        public function __toString(): string
        {
            return 'Michale';
        }
    };

    Assert::same('Katalog česky', $catalogCs->translate($stringableKeyObject));
    Assert::same('Ahoj, Michale!', $catalogCs->translate($namedKey, ['name' => $stringableParameter]));

    Assert::exception(
        static fn () => $catalogCs->translate(123),
        InvalidArgumentException::class,
    );
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "OK\n";
?>
--EXPECT--
OK
