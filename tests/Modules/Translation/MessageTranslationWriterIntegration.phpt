--TEST--
MessageTranslationWriter creates, updates and deletes custom translations, invalidates cache and never changes core sources
--FILE--
<?php

use App\Modules\Translation\Api\CustomMessageNotFoundException;
use App\Modules\Translation\Api\MessageTranslationWriter;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Modules\Translation\Infrastructure\Cache\CachedMessageStore;
use App\Modules\Translation\Infrastructure\Cache\NetteMessageCacheInvalidator;
use App\Modules\Translation\Infrastructure\Diagnostics\MissingTranslationDiagnostics;
use App\Modules\Translation\Infrastructure\Persistence\DatabaseMessageStore;
use App\Modules\Translation\Infrastructure\Persistence\DatabaseMessageTranslationWriter;
use App\Modules\Translation\Infrastructure\Translation\DatabaseTranslatorFactory;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Infrastructure\Persistence\Locale\DatabaseLocaleProvider;
use Monolog\Logger;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
/** @var Explorer $database */
$database = $integration->database;
$pdo = $database->getConnection()->getPdo();
// Každý paralelní integrační test musí mít vlastní cache namespace i storage.
// Sdílená DI cache by při souběhu mohla po invalidaci znovu uložit starý katalog.
$cacheStorage = new class implements Storage {
    /** @var array<string, array{data: mixed, tags: list<string>}> */
    private array $items = [];

    public function read(string $key): mixed
    {
        return $this->items[$key]['data'] ?? null;
    }

    public function lock(string $key): void
    {
    }

    public function write(string $key, mixed $data, array $dependencies): void
    {
        $this->items[$key] = [
            'data' => $data,
            'tags' => array_map(
                static fn (mixed $tag): string => (string) $tag,
                (array) ($dependencies[Cache::Tags] ?? []),
            ),
        ];
    }

    public function remove(string $key): void
    {
        unset($this->items[$key]);
    }

    public function clean(array $conditions): void
    {
        if (!empty($conditions[Cache::All])) {
            $this->items = [];
            return;
        }

        $tags = array_map(
            static fn (mixed $tag): string => (string) $tag,
            (array) ($conditions[Cache::Tags] ?? []),
        );

        foreach ($this->items as $key => $item) {
            if (array_intersect($tags, $item['tags']) !== []) {
                unset($this->items[$key]);
            }
        }
    }
};

$locale = Locale::fromString('cs');
$token = bin2hex(random_bytes(8));
$cache = new Cache($cacheStorage, 'translation-writer-' . $token);
$cachedMessageStore = new CachedMessageStore(new DatabaseMessageStore($database), $cache);
$cacheInvalidator = new NetteMessageCacheInvalidator($cache);
$writer = new DatabaseMessageTranslationWriter($database, $cacheInvalidator);
$translatorFactory = new DatabaseTranslatorFactory(
    $cachedMessageStore,
    new DatabaseLocaleProvider($database),
    new MissingTranslationDiagnostics(new Logger('translation-writer-test')),
);

$customKey = 'f4_18.custom.' . $token;
$coreKey = 'button.add_to_cart';

$pdo->beginTransaction();

try {
    // Nejdřív uložíme do cache katalog bez nového klíče; createCustom ho musí invalidovat.
    $beforeCreate = $translatorFactory->createTranslator(TranslationDomain::Catalog, $locale);
    Assert::same($customKey, $beforeCreate->translate($customKey));

    $writer->createCustom(TranslationDomain::Catalog, $customKey, $locale, 'Původní vlastní text');

    // F4-20: vlastní text lze založit pouze v některé z explicitně povolených domén.
    $domainTables = [
        TranslationDomain::Catalog->value => 'catalog_message_translation',
        TranslationDomain::Cart->value => 'cart_message_translation',
        TranslationDomain::Checkout->value => 'checkout_message_translation',
        TranslationDomain::Admin->value => 'admin_message_translation',
    ];

    foreach (TranslationDomain::cases() as $domain) {
        $domainKey = 'f4_20.allowed.' . $domain->value . '.' . $token;
        $writer->createCustom($domain, $domainKey, $locale, 'Vlastní text ' . $domain->value);

        $table = $domainTables[$domain->value];
        $statement = $pdo->prepare(
            'SELECT source, message_text FROM ' . $table . ' WHERE message_key = ? AND locale = ?',
        );
        $statement->execute([$domainKey, $locale->getCode()]);
        Assert::same(['source' => 'custom', 'message_text' => 'Vlastní text ' . $domain->value], $statement->fetch(PDO::FETCH_ASSOC));
    }

    $before = $translatorFactory->createTranslator(TranslationDomain::Catalog, $locale);
    Assert::same('Původní vlastní text', $before->translate($customKey));

    $writer->updateCustom(TranslationDomain::Catalog, $customKey, $locale, 'Aktualizovaný vlastní text');

    $after = $translatorFactory->createTranslator(TranslationDomain::Catalog, $locale);
    Assert::same('Aktualizovaný vlastní text', $after->translate($customKey));

    Assert::exception(
        static fn() => $writer->updateCustom(TranslationDomain::Catalog, $coreKey, $locale, 'Nesmí přepsat systémový text'),
        CustomMessageNotFoundException::class,
    );

    Assert::exception(
        static fn() => $writer->deleteCustom(TranslationDomain::Catalog, $coreKey, $locale),
        CustomMessageNotFoundException::class,
    );

    // I odstranění musí zneplatnit katalog, který ještě obsahuje vlastní text.
    $beforeDelete = $translatorFactory->createTranslator(TranslationDomain::Catalog, $locale);
    Assert::same('Aktualizovaný vlastní text', $beforeDelete->translate($customKey));

    $writer->deleteCustom(TranslationDomain::Catalog, $customKey, $locale);
    $afterDelete = $translatorFactory->createTranslator(TranslationDomain::Catalog, $locale);
    Assert::same($customKey, $afterDelete->translate($customKey));

    $row = $pdo->prepare(
        'SELECT COUNT(*) FROM catalog_message_translation WHERE message_key = ? AND locale = ?',
    );
    $row->execute([$customKey, $locale->getCode()]);
    Assert::same(0, (int) $row->fetchColumn());

    Assert::exception(
        static fn() => $writer->createCustom(TranslationDomain::Catalog, 'invalid key', $locale, 'Text'),
        InvalidArgumentException::class,
    );

    Assert::exception(
        static fn() => $writer->createCustom(TranslationDomain::Catalog, 'f4_18.html.' . $token, $locale, '<b>HTML</b>'),
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
