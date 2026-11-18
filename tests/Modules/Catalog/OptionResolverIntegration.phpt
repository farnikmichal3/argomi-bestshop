--TEST--
OptionResolver validates and normalizes customer options for active products
--FILE--
<?php

use App\Modules\Catalog\Application\OptionResolver;
use App\Modules\Catalog\Infrastructure\Persistence\DatabaseOptionResolutionRepository;
use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);

/** @var Explorer $database */
$database = $integration->database;
$resolver = new OptionResolver(new DatabaseOptionResolutionRepository($database));

$resolved = $resolver->resolve(1, [
    ['optionId' => 1, 'valueId' => 2],
    ['optionId' => 2, 'textValue' => '  Moje logo  '],
]);

Assert::notNull($resolved);
Assert::same(1, $resolved->productId);
Assert::count(2, $resolved->options);
Assert::same(1, $resolved->options[0]->optionId);
Assert::same('choice', $resolved->options[0]->type->value);
Assert::same(2, $resolved->options[0]->valueId);
Assert::same(null, $resolved->options[0]->textValue);
Assert::same(2, $resolved->options[1]->optionId);
Assert::same('text', $resolved->options[1]->type->value);
Assert::same(null, $resolved->options[1]->valueId);
Assert::same('Moje logo', $resolved->options[1]->textValue);

// Nepovinná prázdná textová volba se normalizuje na nepřítomnou volbu.
$withoutOptionalText = $resolver->resolve(1, [
    ['optionId' => 1, 'valueId' => 1],
    ['optionId' => 2, 'textValue' => '   '],
]);
Assert::notNull($withoutOptionalText);
Assert::count(1, $withoutOptionalText->options);
Assert::same(1, $withoutOptionalText->options[0]->optionId);

// Neexistující nebo neaktivní produkt.
Assert::same(null, $resolver->resolve(999999, []));

// Povinná volba nesmí chybět.
Assert::exception(
    static fn () => $resolver->resolve(1, []),
    InvalidArgumentException::class,
);

// Neznámá volba ani volba jiného produktu nesmí projít.
Assert::exception(
    static fn () => $resolver->resolve(1, [
        ['optionId' => 999999, 'valueId' => 1],
    ]),
    InvalidArgumentException::class,
);
Assert::exception(
    static fn () => $resolver->resolve(2, [
        ['optionId' => 1, 'valueId' => 1],
    ]),
    InvalidArgumentException::class,
);

// Hodnota musí patřit přesně k odeslané volbě.
Assert::exception(
    static fn () => $resolver->resolve(1, [
        ['optionId' => 1, 'valueId' => 999999],
    ]),
    InvalidArgumentException::class,
);

// Typ volby nelze zaměnit a nelze odeslat obě hodnoty současně.
Assert::exception(
    static fn () => $resolver->resolve(1, [
        ['optionId' => 1, 'textValue' => 'text'],
    ]),
    InvalidArgumentException::class,
);
Assert::exception(
    static fn () => $resolver->resolve(1, [
        ['optionId' => 2, 'valueId' => 1],
    ]),
    InvalidArgumentException::class,
);
Assert::exception(
    static fn () => $resolver->resolve(1, [
        ['optionId' => 1, 'valueId' => 1, 'textValue' => 'text'],
    ]),
    InvalidArgumentException::class,
);

// Duplicitní volby se odmítnou i tehdy, když první nepovinný text zůstane prázdný.
Assert::exception(
    static fn () => $resolver->resolve(1, [
        ['optionId' => 1, 'valueId' => 1],
        ['optionId' => 2, 'textValue' => ''],
        ['optionId' => 2, 'textValue' => 'Druhý pokus'],
    ]),
    InvalidArgumentException::class,
);

// Text se ořízne a nesmí překročit limit databázového sloupce.
Assert::exception(
    static fn () => $resolver->resolve(1, [
        ['optionId' => 1, 'valueId' => 1],
        ['optionId' => 2, 'textValue' => str_repeat('x', 501)],
    ]),
    InvalidArgumentException::class,
);

echo "OK\n";
?>
--EXPECT--
OK
