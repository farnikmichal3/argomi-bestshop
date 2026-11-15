--TEST--
ISO 4217 catalog constrains business currencies and preserves shop activation
--FILE--
<?php

use App\Shared\Domain\Money\Currency;
use App\Shared\Domain\Money\CurrencyProvider;
use App\Shared\Infrastructure\Persistence\Money\DatabaseCurrencyProvider;
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

$provider = new DatabaseCurrencyProvider($database);
Assert::type(CurrencyProvider::class, $provider);

$release = $database->table('iso4217_catalog_release')->where('id', 1)->fetch();
Assert::notNull($release);
Assert::same('2026-09-17', $release->version);
Assert::same('2026-09-17', $release->published_at->format('Y-m-d'));
Assert::same(
    155,
    (int) $database->getConnection()->getPdo()
        ->query('SELECT COUNT(*) FROM iso4217_currency')
        ->fetchColumn(),
);

$leadingZeroNumericCode = $database->table('iso4217_currency')->where('code', 'ALL')->fetch();
Assert::notNull($leadingZeroNumericCode);
Assert::same('008', $leadingZeroNumericCode->numeric_code);

$czkCatalogEntry = $database->table('iso4217_currency')->where('code', 'CZK')->fetch();
Assert::notNull($czkCatalogEntry);
Assert::same(2, (int) $czkCatalogEntry->minor_units);

$default = $provider->getDefault();
Assert::same('CZK', $default->code);
Assert::same(2, $default->minorUnits);

$active = $provider->getActive();
Assert::same(['CZK', 'EUR'], array_map(
    static fn (Currency $currency): string => $currency->code,
    $active,
));

// Kód mimo ISO katalogu nesmí být uložen jako obchodní měna.
Assert::exception(
    static fn () => $database->table('currency')->insert([
        'code' => 'ZZZ',
        'name' => 'Unknown test currency',
        'symbol' => 'ZZZ',
        'minor_units' => 2,
        'is_default' => 0,
        'is_active' => 0,
        'sort_order' => 99,
    ]),
    PDOException::class,
);

// Platný kód s nesprávnou přesností nesmí obejít autoritativní katalog.
Assert::exception(
    static fn () => $database->table('currency')->insert([
        'code' => 'USD',
        'name' => 'US Dollar with invalid precision',
        'symbol' => '$',
        'minor_units' => 4,
        'is_default' => 0,
        'is_active' => 0,
        'sort_order' => 99,
    ]),
    PDOException::class,
);

// Registrace v ISO katalogu sama o sobě měnu v obchodě neaktivuje.
$database->table('currency')->insert([
    'code' => 'USD',
    'name' => 'US Dollar',
    'symbol' => '$',
    'minor_units' => 2,
    'is_default' => 0,
    'is_active' => 0,
    'sort_order' => 99,
]);

try {
    $usd = $database->table('currency')->where('code', 'USD')->fetch();
    Assert::notNull($usd);
    Assert::same(0, (int) $usd->is_active);

    $activeCodes = array_map(
        static fn (Currency $currency): string => $currency->code,
        $provider->getActive(),
    );
    Assert::same(['CZK', 'EUR'], $activeCodes);
} finally {
    $database->table('currency')->where('code', 'USD')->delete();
}

echo "OK\n";
?>
--EXPECT--
OK
