<?php

declare(strict_types=1);

use App\Modules\Catalog\Api\AttributeAdminReader;
use App\Modules\Catalog\Api\AttributeInUseException;
use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Application\AttributeWriteRepository;
use App\Modules\Catalog\Application\AttributeWriteService;
use App\Modules\Catalog\Api\AttributeDataType;
use App\Shared\Application\Transaction\Transaction;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
/** @var Explorer $database */
$database = $integration->database;
/** @var AttributeWriteRepository $repository */
$repository = $integration->container->getByType(AttributeWriteRepository::class);
/** @var Transaction $transaction */
$transaction = $integration->container->getByType(Transaction::class);
/** @var LocaleProvider $localeProvider */
$localeProvider = $integration->container->getByType(LocaleProvider::class);
/** @var AttributeAdminReader $reader */
$reader = $integration->container->getByType(AttributeAdminReader::class);

$permission = new class implements CatalogPermission {
    public function canView(): bool
    {
        return true;
    }

    public function canManage(): bool
    {
        return true;
    }
};
$service = new AttributeWriteService($repository, $localeProvider, $transaction, $permission);
$denied = new AttributeWriteService(
    $repository,
    $localeProvider,
    $transaction,
    new class implements CatalogPermission {
        public function canView(): bool
        {
            return false;
        }

        public function canManage(): bool
        {
            return false;
        }
    },
);

$token = bin2hex(random_bytes(5));
$code = 'test_attribute_' . $token;
$valueCode = 'value_' . $token;
$translations = static function (string $prefix) use ($localeProvider, $token): array {
    $result = [];
    foreach ($localeProvider->getActiveLocales() as $locale) {
        $result[$locale->getCode()] = ['name' => $prefix . '-' . $token . '-' . $locale->getCode()];
    }
    return $result;
};
$valueTranslations = static function (string $prefix) use ($localeProvider, $token): array {
    $result = [];
    foreach ($localeProvider->getActiveLocales() as $locale) {
        $result[$locale->getCode()] = ['label' => $prefix . '-' . $token . '-' . $locale->getCode()];
    }
    return $result;
};

Assert::exception(
    fn () => $denied->create($code, AttributeDataType::Enum, null, false, 0, $translations('denied')),
    CatalogAccessDeniedException::class,
);
Assert::same(null, $database->table('attribute')->where('code', $code)->fetch());

try {
    $database->transaction(function () use ($database, $service, $reader, $localeProvider, $token, $code, $valueCode, $translations, $valueTranslations): void {
        $attributeId = $service->create($code, AttributeDataType::Enum, null, true, 4, $translations('attribute'));
        Assert::true($attributeId > 0);

        $valueId = $service->createValue($attributeId, $valueCode, 2, $valueTranslations('value'));
        Assert::true($valueId > 0);

        $service->update($attributeId, $code, AttributeDataType::Enum, null, false, 7, $translations('updated'));
        $service->updateValue($attributeId, $valueId, $valueCode, 3, $valueTranslations('updated-value'));

        $data = $reader->findForEdit($attributeId);
        Assert::notNull($data);
        Assert::same(AttributeDataType::Enum, $data->type);
        Assert::same(7, $data->sortOrder);
        Assert::false($data->isFilterable);
        Assert::same(count($localeProvider->getActiveLocales()), count($data->translations));
        Assert::count(1, $data->values);
        Assert::same($valueCode, $data->values[0]->code);
        Assert::same(3, $data->values[0]->sortOrder);
        Assert::same(count($localeProvider->getActiveLocales()), count($data->values[0]->translations));

        Assert::exception(
            fn () => $service->createValue($attributeId, $valueCode, 0, $valueTranslations('duplicate')),
            InvalidArgumentException::class,
        );
        Assert::exception(
            fn () => $service->create($code, AttributeDataType::Text, null, false, 0, $translations('duplicate')),
            InvalidArgumentException::class,
        );
        Assert::exception(
            fn () => $service->update($attributeId, $code, AttributeDataType::Text, null, false, 0, $translations('wrong-type')),
            InvalidArgumentException::class,
        );

        $taxClass = $database->table('tax_class')->where('code', 'STANDARD')->fetch();
        Assert::notNull($taxClass);
        $product = $database->table('product')->insert([
            'code' => 'f519-' . $token,
            'status' => 'draft',
            'type' => 'physical',
            'tax_class_id' => (int) $taxClass->id,
        ]);
        $productId = (int) $product->id;
        $variant = $database->table('product_variant')->insert([
            'product_id' => $productId,
            'sku' => 'F519-' . $token,
            'combination_hash' => hash('sha256', 'f519-' . $token),
            'is_default' => 1,
            'is_active' => 1,
        ]);
        $variantId = (int) $variant->id;
        $database->table('product_variant_axis')->insert([
            'product_id' => $productId,
            'attribute_id' => $attributeId,
            'sort_order' => 0,
        ]);
        $database->table('product_variant_attribute_value')->insert([
            'product_variant_id' => $variantId,
            'product_id' => $productId,
            'attribute_id' => $attributeId,
            'attribute_value_id' => $valueId,
            'value_number' => null,
            'value_text' => null,
        ]);

        Assert::exception(
            fn () => $service->deleteValue($attributeId, $valueId),
            AttributeInUseException::class,
        );
        Assert::exception(
            fn () => $service->delete($attributeId),
            AttributeInUseException::class,
        );
        Assert::true($reader->findForEdit($attributeId)?->isUsed ?? false);

        throw new RuntimeException('rollback F5-19 fixture');
    });
} catch (RuntimeException $exception) {
    Assert::same('rollback F5-19 fixture', $exception->getMessage());
}

echo "OK\n";
?>
--EXPECT--
OK
