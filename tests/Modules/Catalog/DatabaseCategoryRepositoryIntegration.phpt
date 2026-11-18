--TEST--
Catalog category repository returns localized, ordered category trees
--FILE--
<?php

use App\\Modules\\Catalog\\Application\\CategoryRepository;
use App\\Shared\\Domain\\Locale\\Locale;
use Tester\\Assert;
use Tests\\Support\\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);

/** @var CategoryRepository $repository */
$repository = $integration->container->getByType(CategoryRepository::class);

$tree = $repository->findTree(Locale::fromString('cs'));

Assert::count(2, $tree);
Assert::same(['clothing', 'electronics'], array_map(
    static fn ($node): string => $node->category->code,
    $tree,
));
Assert::same('Oblečení', $tree[0]->translation?->name);
Assert::same('obleceni', $tree[0]->translation?->slug);
Assert::same([], $tree[0]->children);

Assert::same('Elektronika', $tree[1]->translation?->name);
Assert::same(['cables', 'chargers'], array_map(
    static fn ($node): string => $node->category->code,
    $tree[1]->children,
));
Assert::same(['Kabely', 'Nabíječky'], array_map(
    static fn ($node): ?string => $node->translation?->name,
    $tree[1]->children,
));

$activeTree = $repository->findActiveTree(Locale::fromString('cs'));
Assert::same(
    array_map(static fn ($node): int => $node->category->id, $tree),
    array_map(static fn ($node): int => $node->category->id, $activeTree),
);
Assert::same(
    array_map(static fn ($node): int => $node->category->id, $tree[1]->children),
    array_map(static fn ($node): int => $node->category->id, $activeTree[1]->children),
);

$withoutTranslation = $repository->findTree(Locale::fromString('de'));
Assert::count(2, $withoutTranslation);
Assert::same(null, $withoutTranslation[0]->translation);
Assert::same(null, $repository->findTree(Locale::fromString('cs'))[0]->children[0] ?? null);

echo "OK\n";
?>
--EXPECT--
OK
