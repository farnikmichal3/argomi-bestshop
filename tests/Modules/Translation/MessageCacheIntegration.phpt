--TEST--
Cached message store caches by domain and locale and invalidates matching catalog tags
--FILE--
<?php

use App\Modules\Translation\Application\Dto\MessageCatalog;
use App\Modules\Translation\Application\MessageCacheInvalidator;
use App\Modules\Translation\Application\MessageStore;
use App\Modules\Translation\Domain\MessageDomain;
use App\Shared\Domain\Locale\Locale;
use Nette\Caching\Cache;
use Nette\Caching\Storages\MemoryStorage;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
$container = $integration->container;
$cachedStore = $container->getByType(MessageStore::class);
$invalidator = $container->getByType(MessageCacheInvalidator::class);

Assert::type(MessageStore::class, $cachedStore);
Assert::type(MessageCacheInvalidator::class, $invalidator);

$cache = new Cache(new MemoryStorage(), 'translation-cache-test');
$inner = new class implements MessageStore {
    public int $loads = 0;

    public function load(MessageDomain $domain, Locale $locale): MessageCatalog
    {
        $this->loads++;

        return new MessageCatalog($domain, $locale, [
            'test.message' => 'version-' . $this->loads,
        ]);
    }
};

$cached = new App\Modules\Translation\Infrastructure\Cache\CachedMessageStore($inner, $cache);
$cacheInvalidator = new App\Modules\Translation\Infrastructure\Cache\NetteMessageCacheInvalidator($cache);
$localeCs = Locale::fromString('cs');
$localeEn = Locale::fromString('en');

Assert::same('version-1', $cached->load(MessageDomain::Catalog, $localeCs)->get('test.message'));
Assert::same('version-1', $cached->load(MessageDomain::Catalog, $localeCs)->get('test.message'));
Assert::same(1, $inner->loads, 'Second load must be served from cache.');

Assert::same('version-2', $cached->load(MessageDomain::Catalog, $localeEn)->get('test.message'));
Assert::same('version-3', $cached->load(MessageDomain::Cart, $localeCs)->get('test.message'));

$cacheInvalidator->invalidate(MessageDomain::Catalog);
Assert::same('version-4', $cached->load(MessageDomain::Catalog, $localeCs)->get('test.message'));
Assert::same('version-3', $cached->load(MessageDomain::Cart, $localeCs)->get('test.message'));

$cacheInvalidator->invalidate(null, $localeCs);
Assert::same('version-5', $cached->load(MessageDomain::Cart, $localeCs)->get('test.message'));
Assert::same('version-2', $cached->load(MessageDomain::Catalog, $localeEn)->get('test.message'));

$cacheInvalidator->invalidate();
Assert::same('version-6', $cached->load(MessageDomain::Catalog, $localeEn)->get('test.message'));

echo "OK\n";
?>
--EXPECT--
OK
