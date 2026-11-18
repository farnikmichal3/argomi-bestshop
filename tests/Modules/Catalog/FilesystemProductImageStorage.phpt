--TEST--
Filesystem product image storage writes atomically inside its private root and rejects unsafe keys
--FILE--
<?php

use App\Modules\Catalog\Infrastructure\Storage\FilesystemProductImageStorage;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$root = sys_get_temp_dir() . '/argomi-image-storage-' . bin2hex(random_bytes(8));
$storage = new FilesystemProductImageStorage($root);
$key = 'ab/cd/product-image.webp';
$contents = "\x00\x01binary-image-content\xff";

try {
    Assert::false($storage->exists($key));

    $storage->write($key, $contents);

    Assert::true($storage->exists($key));
    Assert::same($contents, $storage->read($key));

    Assert::exception(
        static fn () => $storage->write('../outside.webp', 'unsafe'),
        InvalidArgumentException::class,
    );
    Assert::exception(
        static fn () => $storage->read('/outside.webp'),
        InvalidArgumentException::class,
    );
    Assert::exception(
        static fn () => $storage->delete('ab\\..\\outside.webp'),
        InvalidArgumentException::class,
    );

    $storage->delete($key);
    Assert::false($storage->exists($key));
    Assert::noError(static fn () => $storage->delete($key));
} finally {
    @rmdir($root . '/ab/cd');
    @rmdir($root . '/ab');
    @rmdir($root);
}

echo "OK\n";
?>
--EXPECT--
OK
