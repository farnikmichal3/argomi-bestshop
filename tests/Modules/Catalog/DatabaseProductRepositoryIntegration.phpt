--TEST--
Catalog product repository maps Explorer rows to typed DTOs
--FILE--
<?php

use App\Modules\Catalog\Application\ProductRepository;
use App\Modules\Catalog\Domain\Product\ProductType;
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

/** @var ProductRepository $repository */
$repository = $container->getByType(ProductRepository::class);

// Demo seed obsahuje stabilní produkt a překlady, které používají i další
// read-only integrační testy. Test proto nepotřebuje zapisovat vlastní data.
$product = $repository->findById(1);

Assert::notNull($product);
Assert::same(1, $product->id);
Assert::same('tshirt-printed', $product->code);
Assert::same('active', $product->status);
Assert::same(ProductType::Physical, $product->type);
Assert::same(1, $product->taxClassId);
Assert::same('UTC', $product->createdAt->getTimezone()->getName());
Assert::same('UTC', $product->updatedAt->getTimezone()->getName());

$productByCode = $repository->findByCode('tshirt-printed');

Assert::notNull($productByCode);
Assert::same($product->id, $productByCode->id);
Assert::same(null, $repository->findById(PHP_INT_MAX));
Assert::same(null, $repository->findByCode('catalog-product-that-does-not-exist'));

$czech = $repository->findTranslation(1, Locale::fromString('cs'));

Assert::notNull($czech);
Assert::same(1, $czech->productId);
Assert::same('cs', $czech->locale->getCode());
Assert::same('Tričko s potiskem', $czech->name);
Assert::same('tricko-s-potiskem', $czech->slug);
Assert::same('Bavlněné tričko s potiskem pro každý den', $czech->shortDescription);
Assert::same('<p>Pohodlné bavlněné tričko pro každodenní nošení. Konkrétní varianta určuje barvu a velikost.</p><ul><li>Materiál: bavlna</li><li>Barvy: černá a bílá</li><li>Velikosti: S, M a L</li><li>Hmotnost varianty přibližně 180 až 200 g</li></ul><p>Pro zachování potisku perte tričko naruby podle pokynů na štítku.</p>', $czech->description);
Assert::same('Bavlněné tričko s potiskem | ArgoMi', $czech->metaTitle);
Assert::same('Pohodlné bavlněné tričko v černé nebo bílé barvě a velikostech S až L. Vyberte si variantu a vlastní potisk.', $czech->metaDescription);
Assert::same(null, $repository->findTranslation(1, Locale::fromString('de')));

$translations = $repository->findTranslations(1);

Assert::count(2, $translations);
Assert::same(['cs', 'en'], array_map(
    static fn ($translation): string => $translation->locale->getCode(),
    $translations,
));
Assert::same([], $repository->findTranslations(PHP_INT_MAX));

echo "OK\n";
?>
--EXPECT--
OK
