--TEST--
Catalog domain objects enforce catalog invariants without infrastructure dependencies
--FILE--
<?php

use App\Modules\Catalog\Domain\Attribute\Attribute;
use App\Modules\Catalog\Domain\Attribute\AttributeType;
use App\Modules\Catalog\Domain\Category\Category;
use App\Modules\Catalog\Domain\Option\CustomerOption;
use App\Modules\Catalog\Domain\Option\CustomerOptionType;
use App\Modules\Catalog\Domain\Product\Product;
use App\Modules\Catalog\Domain\Product\ProductStatus;
use App\Modules\Catalog\Domain\Product\ProductType;
use App\Modules\Catalog\Domain\Variant\ProductVariant;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

// Kategorie podporuje kořenové i vnořené uzly, včetně řazení a deaktivace.
$rootCategory = new Category(1, null, 'electronics', 10, true);
$childCategory = new Category(2, 1, 'usb-c_cables', 5, false);
Assert::same(null, $rootCategory->parentId);
Assert::same(1, $childCategory->parentId);
Assert::false($childCategory->isActive);

// Produkt má samostatný obchodní typ a stav životního cyklu.
$draft = new Product(1, 'usb-c-cable', ProductStatus::Draft, 1);
$active = new Product(2, 'gan-charger', ProductStatus::Active, 1, ProductType::Physical);
$archived = new Product(3, 'old-product', ProductStatus::Archived, 2);
$digital = new Product(4, 'antivirus', ProductStatus::Active, 1, ProductType::Digital);
$service = new Product(5, 'installation', ProductStatus::Active, 1, ProductType::Service);
Assert::same(ProductType::Physical, $draft->type);
Assert::same(ProductType::Physical, $active->type);
Assert::same(ProductType::Physical, $archived->type);
Assert::same(ProductType::Digital, $digital->type);
Assert::same(ProductType::Service, $service->type);
Assert::false($draft->isPurchasable());
Assert::true($active->isPurchasable());
Assert::true($archived->isArchived());

// Varianta drží fyzické hodnoty v gramech a milimetrech a hash SHA-256.
$variant = new ProductVariant(
    10,
    2,
    'GAN-65W-WHITE',
    hash('sha256', 'color=white;power_w=65'),
    true,
    true,
    90,
    80,
    40,
    30,
    1,
);
Assert::same(90, $variant->weightG);
Assert::same(80, $variant->lengthMm);
Assert::true($variant->isDefault);

// Atributy používají přesně čtyři typy z databázového schématu.
$attribute = new Attribute(1, 'power_w', AttributeType::Number, 6, true, 1);
$enumAttribute = new Attribute(2, 'color', AttributeType::Enum, null, false, 2);
Assert::same(AttributeType::Number, $attribute->type);
Assert::same(AttributeType::Enum, $enumAttribute->type);
Assert::same(6, $attribute->unitId);

// Zákaznické volby odpovídají choice/text a mohou být povinné.
$choice = new CustomerOption(1, 3, 'print', CustomerOptionType::Choice, true, 1);
$text = new CustomerOption(2, 3, 'print_text', CustomerOptionType::Text, false, 2);
Assert::true($choice->isRequired);
Assert::same(CustomerOptionType::Text, $text->type);

// Neplatné identifikátory a hodnoty odmítne doména před vstupem do persistence.
$invalid = [
    static fn () => new Category(0, null, 'valid', 0, true),
    static fn () => new Category(1, 1, 'valid', 0, true),
    static fn () => new Category(1, null, 'Invalid Code', 0, true),
    static fn () => new Product(0, 'valid', ProductStatus::Draft, 1),
    static fn () => new Product(1, 'valid', ProductStatus::Draft, 0),
    static fn () => new Product(1, 'Invalid', ProductStatus::Active, 1),
    static fn () => new ProductVariant(1, 0, 'SKU-1', hash('sha256', 'x'), false, true, null, null, null, null, 0),
    static fn () => new ProductVariant(1, 1, '', hash('sha256', 'x'), false, true, null, null, null, null, 0),
    static fn () => new ProductVariant(1, 1, 'SKU-1', 'not-a-hash', false, true, null, null, null, null, 0),
    static fn () => new ProductVariant(1, 1, 'SKU-1', hash('sha256', 'x'), false, true, -1, null, null, null, 0),
    static fn () => new Attribute(0, 'color', AttributeType::Enum, null, true, 0),
    static fn () => new Attribute(1, 'Color', AttributeType::Enum, null, true, 0),
    static fn () => new Attribute(1, 'color', AttributeType::Enum, 0, true, 0),
    static fn () => new CustomerOption(0, 1, 'print', CustomerOptionType::Choice, true, 0),
    static fn () => new CustomerOption(1, 1, 'Print', CustomerOptionType::Choice, true, 0),
];

foreach ($invalid as $factory) {
    Assert::exception($factory, InvalidArgumentException::class);
}

echo "OK\n";
?>
--EXPECT--
OK
