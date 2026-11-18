--TEST--
LocaleManager adds inactive languages, activates them and protects the default language
--FILE--
<?php

use App\Modules\Translation\Api\DefaultLanguageCannotBeDeactivatedException;
use App\Modules\Translation\Api\LanguageNotFoundException;
use App\Modules\Translation\Api\LocaleManager;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Locale\LocaleProvider;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
/** @var LocaleManager $manager */
$manager = $integration->container->getByType(LocaleManager::class);
/** @var LocaleProvider $provider */
$provider = $integration->container->getByType(LocaleProvider::class);
$database = $integration->database;
$pdo = $database->getConnection()->getPdo();
$locale = Locale::fromString('de');

$pdo->beginTransaction();

try {
    $manager->add($locale, 'German test', 90);
    Assert::false($provider->isActive($locale));

    $inactive = array_values(array_filter(
        $manager->getAll(),
        static fn($item): bool => $item->code === $locale->getCode(),
    ));
    Assert::same(1, count($inactive));
    Assert::false($inactive[0]->isActive);

    $manager->setActive($locale, true);
    Assert::true($provider->isActive($locale));

    $manager->setActive($locale, false);
    Assert::false($provider->isActive($locale));

    Assert::exception(
        static fn() => $manager->setActive(Locale::fromString('cs'), false),
        DefaultLanguageCannotBeDeactivatedException::class,
    );

    Assert::exception(
        static fn() => $manager->setActive(Locale::fromString('zz'), true),
        LanguageNotFoundException::class,
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
