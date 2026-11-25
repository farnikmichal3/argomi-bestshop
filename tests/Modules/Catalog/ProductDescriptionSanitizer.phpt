--TEST--
Product description sanitizer preserves only public media image URLs
--FILE--
<?php

use App\Modules\Catalog\Application\ProductDescriptionSanitizer;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$sanitizer = new ProductDescriptionSanitizer();
$token = str_repeat('a', 64);

$valid = $sanitizer->sanitize('<p>Text</p><img src="/media/' . $token . '/detail" alt="Product" width="640">');
Assert::contains('<p>Text</p>', $valid);
Assert::contains('src="/media/' . $token . '/detail"', $valid);
Assert::contains('alt="Product"', $valid);

$unsafe = $sanitizer->sanitize(
    '<img src="https://attacker.example/image.png">'
    . '<img src="//attacker.example/image.png">'
    . '<img src="/admin/media/image/7">'
    . '<img src="data:image/png;base64,AAAA">'
    . '<img src="/media/not-a-token/detail">'
    . '<img src="/media/' . $token . '/original">',
);
Assert::same('', trim((string) $unsafe));

echo "OK\n";
?>
--EXPECT--
OK
