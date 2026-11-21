--TEST--
Catalog slug service transliterates names and checks uniqueness per target and locale
--FILE--
<?php

use App\Modules\Catalog\Application\SlugService;
use App\Modules\Catalog\Domain\Slug\SlugTarget;
use App\Shared\Domain\Locale\Locale;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);

/** @var SlugService $service */
$service = $integration->container->getByType(SlugService::class);
$cs = Locale::fromString('cs');
$en = Locale::fromString('en');

// Diakritika, mezery a interpunkce se normalizují do stabilního ASCII zápisu.
Assert::same('tricko-s-potiskem', $service->generate(SlugTarget::Product, 'Tričko s potiskem', $cs, 1));
Assert::same('usb-c-kabel', $service->generate(SlugTarget::Product, 'USB-C kabel', $cs, 2));

// Existující slug stejné entity není kolizí; jiná entita dostane číselný suffix.
Assert::same('usb-c-kabel-2', $service->generate(SlugTarget::Product, 'USB-C kabel', $cs, 1));
Assert::same('usb-c-kabel-2', $service->generate(SlugTarget::Product, 'USB-C kabel', $cs));

// Kategorie a produkty mají oddělený prostor slugů.
Assert::same('obleceni', $service->generate(SlugTarget::Category, 'Oblečení', $cs, 1));
Assert::true($service->isAvailable(SlugTarget::Category, 'usb-c-kabel', $cs));
Assert::false($service->isAvailable(SlugTarget::Product, 'usb-c-kabel', $cs));
Assert::true($service->isAvailable(SlugTarget::Product, 'usb-c-kabel', $cs, 2));

// Unikátnost je oddělená podle jazyka.
Assert::true($service->isAvailable(SlugTarget::Product, 'usb-c-kabel', $en));

// Dlouhé názvy zůstávají v limitu VARCHAR(255), včetně kolizního suffixu.
$longName = str_repeat('a', 260);
Assert::same(255, strlen($service->generate(SlugTarget::Product, $longName, $cs)));

// Neplatné názvy, ID a ručně kontrolované slugy jsou odmítnuty.
Assert::exception(
    static fn () => $service->generate(SlugTarget::Product, '🔥', $cs),
    InvalidArgumentException::class,
);
Assert::exception(
    static fn () => $service->generate(SlugTarget::Product, 'valid', $cs, 0),
    InvalidArgumentException::class,
);
Assert::exception(
    static fn () => $service->isAvailable(SlugTarget::Product, 'Invalid Slug', $cs),
    InvalidArgumentException::class,
);

echo "OK\n";
?>
--EXPECT--
OK
