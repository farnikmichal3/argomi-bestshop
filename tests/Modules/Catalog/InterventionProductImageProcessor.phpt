--TEST--
Intervention product image processor normalizes and emits WebP bytes
--FILE--
<?php

use App\Modules\Catalog\Application\ProductImageProcessor;
use App\Modules\Catalog\Infrastructure\Storage\InterventionProductImageProcessor;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$sourceImage = imagecreatetruecolor(32, 24);
$color = imagecolorallocate($sourceImage, 240, 30, 60);
imagefill($sourceImage, 0, 0, $color);

ob_start();
imagepng($sourceImage);
$source = ob_get_clean();
imagedestroy($sourceImage);

$processor = new InterventionProductImageProcessor();
Assert::type(ProductImageProcessor::class, $processor);
$result = $processor->toWebp($source);

Assert::same('webp', $result->format);
Assert::same('image/webp', $result->mimeType);
Assert::same('webp', $result->extension);
Assert::same(32, $result->width);
Assert::same(24, $result->height);
Assert::true(strlen($result->contents) > 0);
$decoded = getimagesizefromstring($result->contents);
Assert::same('image/webp', $decoded['mime']);

Assert::exception(static fn () => $processor->toWebp('', 82), InvalidArgumentException::class);
Assert::exception(static fn () => $processor->toWebp($source, 0), InvalidArgumentException::class);

echo "OK\n";
?>
--EXPECT--
OK
