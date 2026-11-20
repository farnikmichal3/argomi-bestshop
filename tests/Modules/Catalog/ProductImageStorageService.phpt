--TEST--
Product image storage service preserves source, stores all derivatives, and rolls back partial writes
--FILE--
<?php

use App\Modules\Catalog\Application\Dto\ProcessedProductImageDto;
use App\Modules\Catalog\Application\ProductImageProcessor;
use App\Modules\Catalog\Application\ProductImageStorage;
use App\Modules\Catalog\Application\ProductImageStorageService;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$makeDto = static fn (string $bytes, int $width, int $height): ProcessedProductImageDto
    => new ProcessedProductImageDto($bytes, 'webp', 'image/webp', 'webp', $width, $height);

$processor = new class($makeDto) implements ProductImageProcessor {
    public function __construct(private readonly Closure $makeDto) {}
    public function toWebp(string $source, int $quality = 82): ProcessedProductImageDto
    {
        return ($this->makeDto)('converted', 1, 1);
    }
    public function toVariants(string $source, int $quality = 82): array
    {
        return [
            'thumbnail' => ($this->makeDto)('thumb-bytes', 160, 160),
            'listing' => ($this->makeDto)('listing-bytes', 480, 480),
            'detail' => ($this->makeDto)('detail-bytes', 1200, 900),
        ];
    }
};

$storage = new class implements ProductImageStorage {
    public array $files = [];
    public int $writes = 0;
    public ?int $failAtWrite = null;
    public function write(string $key, string $contents): void
    {
        $this->writes++;
        if ($this->failAtWrite === $this->writes) {
            throw new RuntimeException('simulated failure');
        }
        $this->files[$key] = $contents;
    }
    public function read(string $key): string { return $this->files[$key] ?? throw new RuntimeException('missing'); }
    public function delete(string $key): void { unset($this->files[$key]); }
    public function exists(string $key): bool { return isset($this->files[$key]); }
};

$service = new ProductImageStorageService($processor, $storage);
$original = "original-source-bytes";
$result = $service->store($original);

Assert::same(strlen($original), $result->originalBytes);
Assert::same($original, $storage->read($result->originalPath));
Assert::same('thumb-bytes', $storage->read($result->derivativePaths['thumbnail']));
Assert::same('listing-bytes', $storage->read($result->derivativePaths['listing']));
Assert::same('detail-bytes', $storage->read($result->derivativePaths['detail']));
Assert::same(4, count($storage->files));
Assert::true(preg_match('/\Aproducts\/[a-f0-9]{32}\/original\.bin\z/', $result->originalPath) === 1);

$storage->failAtWrite = $storage->writes + 3;
Assert::exception(static fn () => $service->store('rollback-source'), RuntimeException::class);
Assert::same(4, count($storage->files));

echo "OK\n";
?>
--EXPECT--
OK
