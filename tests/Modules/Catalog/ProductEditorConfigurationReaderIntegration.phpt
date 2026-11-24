<?php

declare(strict_types=1);

use App\Modules\Catalog\Api\ProductEditorConfigurationReader;
use App\Shared\Domain\Locale\Locale;
use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
/** @var Explorer $database */
$database = $integration->database;
/** @var ProductEditorConfigurationReader $reader */
$reader = $integration->container->getByType(ProductEditorConfigurationReader::class);

$token = bin2hex(random_bytes(5));
try {
    $database->transaction(function () use ($database, $reader, $token): void {
        $taxClass = $database->table('tax_class')->where('code', 'STANDARD')->fetch();
        Assert::notNull($taxClass);

        $product = $database->table('product')->insert([
            'code' => 'editor-config-' . $token,
            'status' => 'draft',
            'type' => 'physical',
            'tax_class_id' => (int) $taxClass->id,
        ]);
        $productId = (int) $product->id;

        $unit = $database->table('unit')->insert([
            'code' => 'u' . $token,
            'is_active' => 1,
            'sort_order' => 0,
        ]);
        $database->table('unit_translation')->insert([
            'unit_id' => $unit->id,
            'locale' => 'cs',
            'symbol' => 'ks',
            'name' => 'Kus',
        ]);

        $enum = $database->table('attribute')->insert([
            'code' => 'editor_enum_' . $token,
            'type' => 'enum',
            'sort_order' => 1,
        ]);
        $text = $database->table('attribute')->insert([
            'code' => 'editor_text_' . $token,
            'type' => 'text',
            'sort_order' => 2,
        ]);
        $number = $database->table('attribute')->insert([
            'code' => 'editor_number_' . $token,
            'type' => 'number',
            'unit_id' => $unit->id,
            'sort_order' => 3,
        ]);
        $bool = $database->table('attribute')->insert([
            'code' => 'editor_bool_' . $token,
            'type' => 'bool',
            'sort_order' => 4,
        ]);

        foreach ([$enum, $text, $number, $bool] as $index => $attribute) {
            $database->table('attribute_translation')->insert([
                'attribute_id' => $attribute->id,
                'locale' => 'cs',
                'name' => 'Atribut ' . $index,
            ]);
        }

        $value = $database->table('attribute_value')->insert([
            'attribute_id' => $enum->id,
            'code' => 'blue',
            'sort_order' => 0,
        ]);
        $database->table('attribute_value_translation')->insert([
            'attribute_value_id' => $value->id,
            'locale' => 'cs',
            'label' => 'Modrá',
        ]);

        $database->table('product_attribute_value')->insert([
            'product_id' => $productId,
            'attribute_id' => $enum->id,
            'attribute_value_id' => $value->id,
            'value_number' => null,
            'value_text' => null,
        ]);
        $database->table('product_attribute_value')->insert([
            'product_id' => $productId,
            'attribute_id' => $text->id,
            'attribute_value_id' => null,
            'value_number' => null,
            'value_text' => '<b>Bezpečný text</b>',
        ]);
        $database->table('product_attribute_value')->insert([
            'product_id' => $productId,
            'attribute_id' => $number->id,
            'attribute_value_id' => null,
            'value_number' => '12.500000',
            'value_text' => null,
        ]);
        $database->table('product_attribute_value')->insert([
            'product_id' => $productId,
            'attribute_id' => $bool->id,
            'attribute_value_id' => null,
            'value_number' => 1,
            'value_text' => null,
        ]);

        $database->table('product_variant_axis')->insert([
            'product_id' => $productId,
            'attribute_id' => $enum->id,
            'sort_order' => 0,
        ]);
        $database->table('product_variant_axis')->insert([
            'product_id' => $productId,
            'attribute_id' => $number->id,
            'sort_order' => 1,
        ]);

        $data = $reader->findForProduct($productId, Locale::fromString('cs'));
        Assert::notNull($data);
        Assert::true(count($data->availableAttributes) >= 4);
        Assert::same((int) $value->id, $data->assignedValues[(int) $enum->id] ?? null);
        Assert::same('<b>Bezpečný text</b>', $data->assignedValues[(int) $text->id] ?? null);
        Assert::same('12.500000', $data->assignedValues[(int) $number->id] ?? null);
        Assert::same(true, $data->assignedValues[(int) $bool->id] ?? null);
        Assert::same([(int) $enum->id, (int) $number->id], $data->variantAxisIds);

        $enumOption = null;
        $numberOption = null;
        foreach ($data->availableAttributes as $option) {
            if ($option->id === (int) $enum->id) {
                $enumOption = $option;
            }
            if ($option->id === (int) $number->id) {
                $numberOption = $option;
            }
        }
        Assert::notNull($enumOption);
        Assert::same('Atribut 0', $enumOption->name);
        Assert::count(1, $enumOption->values);
        Assert::same('Modrá', $enumOption->values[0]->label);
        Assert::notNull($numberOption);
        Assert::same('Kus', $numberOption->unitName);
        Assert::same('ks', $numberOption->unitSymbol);

        Assert::same(null, $reader->findForProduct(PHP_INT_MAX, Locale::fromString('cs')));
        throw new RuntimeException('rollback product editor configuration fixture');
    });
} catch (RuntimeException $exception) {
    Assert::same('rollback product editor configuration fixture', $exception->getMessage());
}

echo "OK\n";
?>
--EXPECT--
OK
