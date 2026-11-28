--TEST--
VariantSelector validates complete selections and delegates deterministic lookup without a database
--FILE--
<?php

use App\Modules\Catalog\Application\VariantSelectionRepository;
use App\Modules\Catalog\Application\VariantSelector;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$repository = new class implements VariantSelectionRepository {
    /** @var array<string, string>|null */
    public ?array $axisTypes = ['color' => 'enum', 'size' => 'enum'];
    public ?int $variantId = 42;
    public int $axisLookupCount = 0;
    public int $variantLookupCount = 0;
    public ?int $receivedProductId = null;
    public ?string $receivedHash = null;

    public function findActiveAxisTypes(int $productId): ?array
    {
        $this->axisLookupCount++;
        $this->receivedProductId = $productId;

        return $this->axisTypes;
    }

    public function findActiveVariantId(int $productId, string $combinationHash): ?int
    {
        $this->variantLookupCount++;
        $this->receivedProductId = $productId;
        $this->receivedHash = $combinationHash;

        return $this->variantId;
    }
};

$selector = new VariantSelector($repository);
$selection = [
    'size' => ['type' => 'enum', 'value' => 'm'],
    'color' => ['type' => 'enum', 'value' => 'black'],
];

Assert::same(42, $selector->select(7, $selection));
Assert::same(7, $repository->receivedProductId);
Assert::same(hash('sha256', 'color=black;size=m'), $repository->receivedHash);
Assert::same(1, $repository->axisLookupCount);
Assert::same(1, $repository->variantLookupCount);

// Pořadí vstupních os nemění výsledek ani hash předaný repository.
$firstHash = $repository->receivedHash;
Assert::same(42, $selector->select(7, array_reverse($selection)));
Assert::same($firstHash, $repository->receivedHash);

// Neexistující/neaktivní produkt se vrací jako null bez hledání varianty.
$repository->axisTypes = null;
Assert::same(null, $selector->select(999, $selection));
Assert::same(2, $repository->axisLookupCount);
Assert::same(2, $repository->variantLookupCount);

// Produkt bez definovaných os je chybná konfigurace.
$repository->axisTypes = [];
Assert::exception(
    static fn () => $selector->select(7, $selection),
    InvalidArgumentException::class,
);

// ID produktu musí být kladné.
Assert::exception(
    static fn () => $selector->select(0, $selection),
    InvalidArgumentException::class,
);

// Výběr musí obsahovat všechny osy, žádnou navíc a správné typy.
$repository->axisTypes = ['color' => 'enum', 'size' => 'enum'];
Assert::exception(
    static fn () => $selector->select(7, ['color' => $selection['color']]),
    InvalidArgumentException::class,
);
Assert::exception(
    static fn () => $selector->select(7, [
        ...$selection,
        'material' => ['type' => 'enum', 'value' => 'cotton'],
    ]),
    InvalidArgumentException::class,
);
Assert::exception(
    static fn () => $selector->select(7, [
        'color' => ['type' => 'enum', 'value' => 'black'],
        'size' => ['type' => 'text', 'value' => 'm'],
    ]),
    InvalidArgumentException::class,
);

// Úplná, typově správná kombinace bez odpovídající aktivní varianty vrátí null.
$repository->variantId = null;
Assert::same(null, $selector->select(7, $selection));

echo "OK\n";
?>
--EXPECT--
OK
