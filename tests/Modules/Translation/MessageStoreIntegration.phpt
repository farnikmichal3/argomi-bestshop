--TEST--
MessageStore loads each owning domain and locale in one query and returns an immutable typed catalog
--FILE--
<?php

use App\Modules\Translation\Application\MessageStore;
use App\Modules\Translation\Domain\MessageDomain;
use App\Shared\Domain\Locale\Locale;
use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
$container = $integration->container;

/** @var Explorer $database */
$database = $integration->database;
$pdo = $database->getConnection()->getPdo();
$store = $container->getByType(MessageStore::class);

Assert::type(MessageStore::class, $store);

$token = bin2hex(random_bytes(8));
$catalogKey = 'f4_02.catalog.' . $token;
$cartKey = 'f4_02.cart.' . $token;
$checkoutKey = 'f4_02.checkout.' . $token;
$emptyLocale = 'qz';

$pdo->beginTransaction();

try {
    $pdo->prepare('INSERT INTO language (code, name) VALUES (?, ?)')->execute([
        $emptyLocale,
        'F4-02 empty-catalog test',
    ]);

    foreach ([
        ['catalog_message_translation', $catalogKey, 'cs', 'Katalog česky'],
        ['catalog_message_translation', $catalogKey, 'en', 'Catalog in English'],
        ['cart_message_translation', $cartKey, 'cs', 'Košík česky'],
        ['checkout_message_translation', $checkoutKey, 'cs', 'Pokladna česky'],
    ] as [$table, $key, $locale, $message]) {
        $statement = $pdo->prepare(sprintf(
            'INSERT INTO %s (message_key, locale, message_text) VALUES (?, ?, ?)',
            $table,
        ));
        $statement->execute([$key, $locale, $message]);
    }

    $connection = $database->getConnection();
    $queryCount = 0;
    $connection->onQuery[] = static function () use (&$queryCount): void {
        $queryCount++;
    };

    $catalog = $store->load(MessageDomain::Catalog, Locale::fromString('cs'));

    Assert::same(1, $queryCount, 'Loading one domain and locale must execute exactly one query.');
    Assert::same(MessageDomain::Catalog, $catalog->domain);
    Assert::same('cs', $catalog->locale->getCode());
    Assert::same('Katalog česky', $catalog->get($catalogKey));
    Assert::null($catalog->get('f4_02.missing.' . $token));

    $allMessages = $catalog->all();
    $allMessages[$catalogKey] = 'Externí změna';

    Assert::same(
        'Katalog česky',
        $catalog->get($catalogKey),
        'Changing the returned array must not mutate the catalog.',
    );

    $englishCatalog = $store->load(MessageDomain::Catalog, Locale::fromString('en'));
    Assert::same('Catalog in English', $englishCatalog->get($catalogKey));
    Assert::same(null, $englishCatalog->get($cartKey));

    $cart = $store->load(MessageDomain::Cart, Locale::fromString('cs'));
    Assert::same('Košík česky', $cart->get($cartKey));
    Assert::same(null, $cart->get($catalogKey));

    $checkout = $store->load(MessageDomain::Checkout, Locale::fromString('cs'));
    Assert::same('Pokladna česky', $checkout->get($checkoutKey));

    $empty = $store->load(MessageDomain::Checkout, Locale::fromString($emptyLocale));
    Assert::same([], $empty->all());
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "OK\n";
?>
--EXPECT--
OK
