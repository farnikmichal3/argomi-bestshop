<?php

declare(strict_types=1);

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Application\ProductCustomerOptionQueryService;
use App\Modules\Catalog\Application\ProductCustomerOptionRepository;
use App\Modules\Catalog\Application\ProductCustomerOptionService;
use App\Shared\Application\Transaction\Transaction;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Database\Explorer;
use RuntimeException;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
/** @var Explorer $database */
$database = $integration->database;
/** @var ProductCustomerOptionRepository $repository */
$repository = $integration->container->getByType(ProductCustomerOptionRepository::class);
/** @var Transaction $transaction */
$transaction = $integration->container->getByType(Transaction::class);
/** @var LocaleProvider $localeProvider */
$localeProvider = $integration->container->getByType(LocaleProvider::class);

$permission = new class implements CatalogPermission {
    public function canView(): bool { return true; }
    public function canManage(): bool { return true; }
};
$service = new ProductCustomerOptionService($repository, $localeProvider, $transaction, $permission);
$reader = new ProductCustomerOptionQueryService($repository);

$token = bin2hex(random_bytes(5));
try {
    $database->transaction(function () use ($database, $service, $reader, $repository, $transaction, $localeProvider, $token): void {
        $taxClass = $database->table('tax_class')->order('id ASC')->fetch();
        Assert::notNull($taxClass);
        $product = $database->table('product')->insert([
            'code' => 'test-customer-options-' . $token,
            'status' => 'draft',
            'type' => 'physical',
            'tax_class_id' => $taxClass->id,
        ]);
        $productId = (int) $product->id;

        $service->save($productId, [
            [
                'code' => 'print_type',
                'type' => 'choice',
                'isRequired' => true,
                'sortOrder' => 0,
                'translations' => ['cs' => 'Typ potisku', 'en' => 'Print type'],
                'values' => [
                    [
                        'code' => 'front',
                        'sortOrder' => 0,
                        'translations' => ['cs' => 'Přední strana', 'en' => 'Front'],
                    ],
                    [
                        'code' => 'back',
                        'sortOrder' => 1,
                        'translations' => ['cs' => 'Zadní strana', 'en' => 'Back'],
                    ],
                ],
            ],
            [
                'code' => 'dedication',
                'type' => 'text',
                'isRequired' => false,
                'sortOrder' => 1,
                'translations' => ['cs' => 'Věnování', 'en' => 'Dedication'],
                'values' => [],
            ],
        ]);

        $configuration = $reader->findForProduct($productId);
        Assert::notNull($configuration);
        Assert::count(2, $configuration->options);
        $choice = $configuration->options[0];
        $text = $configuration->options[1];
        Assert::same('print_type', $choice->code);
        Assert::same('Typ potisku', $choice->translations['cs']);
        Assert::same('Print type', $choice->translations['en']);
        Assert::same('choice', $choice->type);
        Assert::true($choice->isRequired);
        Assert::count(2, $choice->values);
        Assert::same('Přední strana', $choice->values[0]->translations['cs']);
        Assert::same('Front', $choice->values[0]->translations['en']);
        Assert::same('dedication', $text->code);
        Assert::same('text', $text->type);
        Assert::count(0, $text->values);

        // Aktualizace zachovává ID existující volby i hodnoty a může přidat další hodnotu.
        $service->save($productId, [
            [
                'id' => $choice->id,
                'code' => 'print_type',
                'type' => 'choice',
                'isRequired' => false,
                'sortOrder' => 1,
                'translations' => ['cs' => 'Umístění potisku', 'en' => 'Print position'],
                'values' => [
                    [
                        'id' => $choice->values[0]->id,
                        'code' => 'front',
                        'sortOrder' => 0,
                        'translations' => ['cs' => 'Přední strana', 'en' => 'Front'],
                    ],
                    [
                        'id' => $choice->values[1]->id,
                        'code' => 'back',
                        'sortOrder' => 1,
                        'translations' => ['cs' => 'Zadní strana', 'en' => 'Back'],
                    ],
                    [
                        'code' => 'sleeve',
                        'sortOrder' => 2,
                        'translations' => ['cs' => 'Rukáv', 'en' => 'Sleeve'],
                    ],
                ],
            ],
            [
                'id' => $text->id,
                'code' => 'dedication',
                'type' => 'text',
                'isRequired' => true,
                'sortOrder' => 0,
                'translations' => ['cs' => 'Text věnování', 'en' => 'Dedication text'],
                'values' => [],
            ],
        ]);
        $updated = $reader->findForProduct($productId);
        Assert::notNull($updated);
        Assert::same($choice->id, $updated->options[0]->id);
        Assert::same('Umístění potisku', $updated->options[0]->translations['cs']);
        Assert::count(3, $updated->options[0]->values);
        Assert::same($choice->values[0]->id, $updated->options[0]->values[0]->id);
        Assert::same(true, $updated->options[1]->isRequired);

        // Neplatné typy, duplicitní kódy a chybějící aktivní překlady jsou odmítnuty.
        Assert::exception(
            fn () => $service->save($productId, [[
                'code' => 'bad_text',
                'type' => 'text',
                'isRequired' => false,
                'sortOrder' => 0,
                'translations' => ['cs' => 'Text', 'en' => 'Text'],
                'values' => [[
                    'code' => 'invalid',
                    'sortOrder' => 0,
                    'translations' => ['cs' => 'Hodnota', 'en' => 'Value'],
                ]],
            ]]),
            InvalidArgumentException::class,
        );
        Assert::exception(
            fn () => $service->save($productId, [
                [
                    'code' => 'duplicate',
                    'type' => 'text',
                    'isRequired' => false,
                    'sortOrder' => 0,
                    'translations' => ['cs' => 'První', 'en' => 'First'],
                    'values' => [],
                ],
                [
                    'code' => 'duplicate',
                    'type' => 'text',
                    'isRequired' => false,
                    'sortOrder' => 1,
                    'translations' => ['cs' => 'Druhá', 'en' => 'Second'],
                    'values' => [],
                ],
            ]),
            InvalidArgumentException::class,
        );
        Assert::exception(
            fn () => $service->save($productId, [[
                'code' => 'missing_translation',
                'type' => 'text',
                'isRequired' => false,
                'sortOrder' => 0,
                'translations' => ['cs' => 'Chybí angličtina'],
                'values' => [],
            ]]),
            InvalidArgumentException::class,
        );

        // Košík používá stabilní ID volby a hodnoty; obojí proto nelze odstranit.
        $variant = $database->table('product_variant')->insert([
            'product_id' => $productId,
            'sku' => 'TEST-OPTION-' . strtoupper($token),
            'combination_hash' => hash('sha256', 'option-' . $token),
        ]);
        $currency = $database->table('currency')->order('code ASC')->fetch();
        $language = $database->table('language')->where('code', 'cs')->fetch();
        Assert::notNull($currency);
        Assert::notNull($language);
        $cart = $database->table('cart')->insert([
            'public_id' => strtoupper(substr(hash('sha256', 'cart-' . $token), 0, 26)),
            'currency' => $currency->code,
            'locale' => $language->code,
        ]);
        $cartItem = $database->table('cart_item')->insert([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'product_id' => $productId,
            'quantity' => '1.000',
            'configuration_hash' => hash('sha256', 'cart-item-' . $token),
        ]);
        $database->table('cart_item_option')->insert([
            'cart_item_id' => $cartItem->id,
            'product_id' => $productId,
            'product_option_id' => $choice->id,
            'product_option_value_id' => $choice->values[0]->id,
            'text_value' => null,
        ]);

        Assert::exception(
            fn () => $service->save($productId, []),
            InvalidArgumentException::class,
        );
        Assert::exception(
            fn () => $service->save($productId, [[
                'id' => $choice->id,
                'code' => 'print_type',
                'type' => 'choice',
                'isRequired' => false,
                'sortOrder' => 0,
                'translations' => ['cs' => 'Typ potisku', 'en' => 'Print type'],
                'values' => [
                    [
                        'id' => $choice->values[1]->id,
                        'code' => 'back',
                        'sortOrder' => 0,
                        'translations' => ['cs' => 'Zadní strana', 'en' => 'Back'],
                    ],
                ],
            ]]),
            InvalidArgumentException::class,
        );

        $denied = new ProductCustomerOptionService(
            $repository,
            $localeProvider,
            $transaction,
            new class implements CatalogPermission {
                public function canView(): bool { return true; }
                public function canManage(): bool { return false; }
            },
        );
        Assert::exception(
            fn () => $denied->save($productId, []),
            CatalogAccessDeniedException::class,
        );

        throw new RuntimeException('rollback customer option fixture');
    });
} catch (RuntimeException $exception) {
    Assert::same('rollback customer option fixture', $exception->getMessage());
}

echo "OK\n";
?>
--EXPECT--
OK
