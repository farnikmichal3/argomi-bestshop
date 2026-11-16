--TEST--
Database translator implements Nette Translator and isolates explicit domain and locale catalogs
--FILE--
<?php

use App\Modules\Translation\Application\MessageCacheInvalidator;
use App\Modules\Translation\Domain\MessageDomain;
use App\Modules\Translation\Infrastructure\Translation\DatabaseTranslatorFactory;
use App\Shared\Domain\Locale\Locale;
use Nette\Localization\Translator;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
$container = $integration->container;
$pdo = $integration->database->getConnection()->getPdo();

/** @var DatabaseTranslatorFactory $factory */
$factory = $container->getByType(DatabaseTranslatorFactory::class);
Assert::type(DatabaseTranslatorFactory::class, $factory);

/** @var MessageCacheInvalidator $cacheInvalidator */
$cacheInvalidator = $container->getByType(MessageCacheInvalidator::class);

$token = bin2hex(random_bytes(8));
$messageKey = 'f4_04.shared_key.' . $token;
$missingKey = 'f4_04.missing_key.' . $token;

$pdo->beginTransaction();

try {
    foreach ([
        ['catalog_message_translation', $messageKey, 'cs', 'Katalog česky'],
        ['catalog_message_translation', $messageKey, 'en', 'Catalog in English'],
        ['cart_message_translation', $messageKey, 'cs', 'Košík česky'],
    ] as [$table, $key, $locale, $message]) {
        $statement = $pdo->prepare(sprintf(
            'INSERT INTO %s (message_key, locale, message_text) VALUES (?, ?, ?)',
            $table,
        ));
        $statement->execute([$key, $locale, $message]);
    }

    $catalogCs = $factory->create(MessageDomain::Catalog, Locale::fromString('cs'));
    $catalogEn = $factory->create(MessageDomain::Catalog, Locale::fromString('en'));
    $cartCs = $factory->create(MessageDomain::Cart, Locale::fromString('cs'));

    Assert::type(Translator::class, $catalogCs);
    Assert::same('Katalog česky', $catalogCs->translate($messageKey));
    Assert::same('Catalog in English', $catalogEn->translate($messageKey));
    Assert::same('Košík česky', $cartCs->translate($messageKey));

    // Neznámý klíč se do zavedení fallbacku vrací beze změny.
    Assert::same($missingKey, $catalogCs->translate($missingKey));

    // Parametry přijímá kontrakt Nette; ICU formátování patří do F4-05.
    Assert::same('Katalog česky', $catalogCs->translate($messageKey, 'ignored'));

    $stringableKey = new class($messageKey) implements Stringable {
        public function __construct(
            private string $value,
        ) {}

        public function __toString(): string
        {
            return $this->value;
        }
    };

    Assert::same('Katalog česky', $catalogCs->translate($stringableKey));

    Assert::exception(
        static fn () => $catalogCs->translate(123),
        InvalidArgumentException::class,
    );
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Testovací katalogy vznikají uvnitř rollbacknuté transakce; nesmí zůstat v persistentní cache.
    $cacheInvalidator->invalidate();
}

echo "OK\n";
?>
--EXPECT--
OK
