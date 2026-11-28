--TEST--
OptionResolver validates and normalizes selections using an in-memory repository
--FILE--
<?php

use App\Modules\Catalog\Application\Dto\CustomerOptionDefinitionDto;
use App\Modules\Catalog\Application\OptionResolutionRepository;
use App\Modules\Catalog\Application\OptionResolver;
use App\Modules\Catalog\Domain\Option\CustomerOption;
use App\Modules\Catalog\Domain\Option\CustomerOptionType;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$choice = new CustomerOption(1, 10, 'print_color', CustomerOptionType::Choice, true, 1);
$requiredText = new CustomerOption(2, 10, 'engraving', CustomerOptionType::Text, true, 2);
$optionalText = new CustomerOption(3, 10, 'note', CustomerOptionType::Text, false, 3);

$repository = new class($choice, $requiredText, $optionalText) implements OptionResolutionRepository {
    /** @var list<CustomerOptionDefinitionDto>|null */
    public ?array $definitions;
    public int $lookupCount = 0;
    public ?int $receivedProductId = null;

    public function __construct(
        CustomerOption $choice,
        CustomerOption $requiredText,
        CustomerOption $optionalText,
    ) {
        $this->definitions = [
            new CustomerOptionDefinitionDto($choice, [11, 12]),
            new CustomerOptionDefinitionDto($requiredText, []),
            new CustomerOptionDefinitionDto($optionalText, []),
        ];
    }

    public function findActiveOptionDefinitions(int $productId): ?array
    {
        $this->lookupCount++;
        $this->receivedProductId = $productId;

        return $this->definitions;
    }
};

$resolver = new OptionResolver($repository);
$resolved = $resolver->resolve(10, [
    ['optionId' => 3, 'textValue' => '  poznámka  '],
    ['optionId' => 2, 'textValue' => '  Moje logo  '],
    ['optionId' => 1, 'valueId' => 12],
]);

Assert::notNull($resolved);
Assert::same(10, $resolved->productId);
Assert::same(10, $repository->receivedProductId);
Assert::count(3, $resolved->options);
// Výsledek je deterministicky seřazený podle optionId, nikoli podle vstupu.
Assert::same([1, 2, 3], array_map(static fn ($option) => $option->optionId, $resolved->options));
Assert::same(CustomerOptionType::Choice, $resolved->options[0]->type);
Assert::same(12, $resolved->options[0]->valueId);
Assert::same(null, $resolved->options[0]->textValue);
Assert::same(CustomerOptionType::Text, $resolved->options[1]->type);
Assert::same(null, $resolved->options[1]->valueId);
Assert::same('Moje logo', $resolved->options[1]->textValue);
Assert::same('poznámka', $resolved->options[2]->textValue);

// Nepovinná prázdná textová volba se vynechá; povinné volby zůstávají.
$withoutOptional = $resolver->resolve(10, [
    ['optionId' => 1, 'valueId' => 11],
    ['optionId' => 2, 'textValue' => 'text'],
    ['optionId' => 3, 'textValue' => '   '],
]);
Assert::notNull($withoutOptional);
Assert::same([1, 2], array_map(static fn ($option) => $option->optionId, $withoutOptional->options));

// Neexistující/neaktivní produkt vrací null.
$repository->definitions = null;
Assert::same(null, $resolver->resolve(999, []));
$repository->definitions = [
    new CustomerOptionDefinitionDto($choice, [11, 12]),
    new CustomerOptionDefinitionDto($requiredText, []),
    new CustomerOptionDefinitionDto($optionalText, []),
];

// Neplatné ID a struktura vstupu.
Assert::exception(static fn () => $resolver->resolve(0, []), InvalidArgumentException::class);
Assert::exception(static fn () => $resolver->resolve(10, ['not-a-list' => []]), InvalidArgumentException::class);
Assert::exception(static fn () => $resolver->resolve(10, [['optionId' => '1', 'valueId' => 11]]), InvalidArgumentException::class);
Assert::exception(static fn () => $resolver->resolve(10, [['optionId' => 0, 'valueId' => 11]]), InvalidArgumentException::class);

// Povinné volby, vlastnictví volby a povolené hodnoty.
Assert::exception(static fn () => $resolver->resolve(10, []), InvalidArgumentException::class);
Assert::exception(static fn () => $resolver->resolve(10, [['optionId' => 999, 'valueId' => 11]]), InvalidArgumentException::class);
Assert::exception(static fn () => $resolver->resolve(10, [['optionId' => 1, 'valueId' => 999]]), InvalidArgumentException::class);
Assert::exception(static fn () => $resolver->resolve(10, [['optionId' => 1, 'valueId' => '11']]), InvalidArgumentException::class);

// Typ hodnoty musí odpovídat definici a musí být předána právě jedna hodnota.
Assert::exception(static fn () => $resolver->resolve(10, [
    ['optionId' => 1, 'textValue' => 'text'],
    ['optionId' => 2, 'textValue' => 'text'],
]), InvalidArgumentException::class);
Assert::exception(static fn () => $resolver->resolve(10, [
    ['optionId' => 1, 'valueId' => 11, 'textValue' => 'text'],
    ['optionId' => 2, 'textValue' => 'text'],
]), InvalidArgumentException::class);
Assert::exception(static fn () => $resolver->resolve(10, [
    ['optionId' => 1, 'valueId' => 11],
    ['optionId' => 2, 'valueId' => 11],
]), InvalidArgumentException::class);

// Duplicitní volby se odmítnou i tehdy, když první nepovinný text zůstane prázdný.
Assert::exception(static fn () => $resolver->resolve(10, [
    ['optionId' => 1, 'valueId' => 11],
    ['optionId' => 2, 'textValue' => 'text'],
    ['optionId' => 3, 'textValue' => ''],
    ['optionId' => 3, 'textValue' => 'druhá hodnota'],
]), InvalidArgumentException::class);

// Text se ořízne, musí být neprázdný u povinné volby a nejvýše 500 znaků.
Assert::exception(static fn () => $resolver->resolve(10, [
    ['optionId' => 1, 'valueId' => 11],
    ['optionId' => 2, 'textValue' => '   '],
]), InvalidArgumentException::class);
Assert::exception(static fn () => $resolver->resolve(10, [
    ['optionId' => 1, 'valueId' => 11],
    ['optionId' => 2, 'textValue' => str_repeat('x', 501)],
]), InvalidArgumentException::class);

// Limit se počítá ve znacích UTF-8, nikoli v bajtech.
$unicodeText = str_repeat('č', 500);
$unicodeResolved = $resolver->resolve(10, [
    ['optionId' => 1, 'valueId' => 11],
    ['optionId' => 2, 'textValue' => $unicodeText],
]);
Assert::notNull($unicodeResolved);
Assert::same($unicodeText, $unicodeResolved->options[1]->textValue);

echo "OK\n";
?>
--EXPECT--
OK
