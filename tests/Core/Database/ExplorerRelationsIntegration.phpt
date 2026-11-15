--TEST--
Nette Database Explorer resolves foreign-key relations and rejects ambiguous references
--FILE--
<?php

use Tests\\Support\\IntegrationTestBootstrap;

use App\Bootstrap;
use Nette\Database\Conventions\AmbiguousReferenceKeyException;
use Nette\Database\Explorer;
use Tester\Assert;
use Tester\Environment;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
$container = $integration->container;

/** @var Explorer $database */
$database = $integration->database;

/**
 * Převede výsledek related() na seřazená ID, aby test nezávisel na pořadí řádků v databázi.
 *
 * @param iterable< object{ id: int|string } > $rows
 * @return list<int>
 */
$sortedIds = static function (iterable $rows): array {
    $ids = [];

    foreach ($rows as $row) {
        $ids[] = (int) $row->id;
    }

    sort($ids);

    return $ids;
};

// 1:N a N:1 – překlady produktu. ref() jde od záznamu s FK k rodiči;
// related() jde opačným směrem a automaticky odvodí jediný odpovídající FK.
$productTranslation = $database->table('product_translation')
    ->where('product_id', 1)
    ->where('locale', 'cs')
    ->fetch();

Assert::notNull($productTranslation);
$product = $productTranslation->ref('product', 'product_id');
Assert::notNull($product);
Assert::same(1, (int) $product->id);
Assert::same('tshirt-printed', $product->code);
Assert::same([1, 2], $sortedIds($product->related('product_translation')));

// Samovazba kategorie – dítě odkazuje na rodiče přes parent_id.
$childCategory = $database->table('category')->where('id', 3)->fetch();
$parentCategory = $database->table('category')->where('id', 2)->fetch();

Assert::notNull($childCategory);
Assert::notNull($parentCategory);
$resolvedParent = $childCategory->ref('category', 'parent_id');
Assert::notNull($resolvedParent);
Assert::same(2, (int) $resolvedParent->id);
Assert::same([3, 4], $sortedIds($parentCategory->related('category', 'parent_id')));

// Vyhledání podle cílové tabulky "product" je nejednoznačné:
// na produkt odkazuje více různých tabulek. Nette musí tuto nejednoznačnost odmítnout.
Assert::exception(
    static fn () => $product->related('product'),
    AmbiguousReferenceKeyException::class,
);

$relationFromCharger = $database->table('product_relation')
    ->where('product_id', 3)
    ->where('related_product_id', 2)
    ->fetch();

Assert::notNull($relationFromCharger);
$sourceProduct = $relationFromCharger->ref('product', 'product_id');
$relatedProduct = $relationFromCharger->ref('product', 'related_product_id');

Assert::notNull($sourceProduct);
Assert::notNull($relatedProduct);
Assert::same(3, (int) $sourceProduct->id);
Assert::same(2, (int) $relatedProduct->id);
// Explicitní sloupec odstraní nejednoznačnost dvou FK ve stejné vazební tabulce.
Assert::same([1], $sortedIds(
    $sourceProduct->related('product_relation', 'product_id'),
));
Assert::same([1], $sortedIds(
    $relatedProduct->related('product_relation', 'related_product_id'),
));

// Tečková syntaxe je alternativní zápis stejného explicitního vztahu.
Assert::same([1], $sortedIds(
    $sourceProduct->related('product_relation.product_id'),
));
Assert::same([1], $sortedIds(
    $relatedProduct->related('product_relation.related_product_id'),
));

// M:N – produkt a kategorie jsou propojené přes junction tabulku product_category.
// Explorer vrací vazební řádky; další ref() vede na cílovou kategorii.
$productCategoryRows = $database->table('product')
    ->where('id', 2)
    ->fetch()
    ?->related('product_category');

Assert::notNull($productCategoryRows);

// U M:N vztahu ověřujeme cizí klíč kategorie, nikoli vlastní ID vazebního řádku.
$relatedCategoryIds = [];

foreach ($productCategoryRows as $productCategoryRow) {
    $relatedCategoryIds[] = (int) $productCategoryRow->category_id;
}

sort($relatedCategoryIds);
Assert::same([3], $relatedCategoryIds);

$productCategory = $database->table('product_category')
    ->where('product_id', 2)
    ->where('category_id', 3)
    ->fetch();

Assert::notNull($productCategory);
$category = $productCategory->ref('category', 'category_id');
Assert::notNull($category);
Assert::same('cables', $category->code);
Assert::same([2], $sortedIds($category->related('product_category', 'category_id')));

echo "OK\n";
?>
--EXPECT--
OK
