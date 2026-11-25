--TEST--
Media asset service validates uploads, stores originals and derivatives, and enforces permissions
--FILE--
<?php

use App\Modules\Media\Api\MediaAccessDeniedException;
use App\Modules\Media\Api\MediaPermission;
use App\Modules\Media\Application\Dto\MediaAssetRecord;
use App\Modules\Media\Application\Dto\MediaAssetDerivativeRecord;
use App\Modules\Media\Application\Dto\ProcessedMediaImageDto;
use App\Modules\Media\Application\Dto\StoredMediaAssetDto;
use App\Modules\Media\Application\MediaAssetRepository;
use App\Modules\Media\Application\MediaAssetService;
use App\Modules\Media\Application\MediaFileStorage;
use App\Modules\Media\Application\MediaImageProcessor;
use App\Shared\Application\Transaction\Transaction;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lX8AAAAASUVORK5CYII=', true);
Assert::type('string', $png);

$processor = new class implements MediaImageProcessor {
    public function toVariants(string $source, int $quality = 82): array
    {
        $make = static fn (int $w, int $h): ProcessedMediaImageDto
            => new ProcessedMediaImageDto('webp-bytes', 'webp', 'image/webp', $w, $h);
        return [
            'thumbnail' => $make(160, 160),
            'listing' => $make(480, 480),
            'detail' => $make(1, 1),
        ];
    }
};
$storage = new class implements MediaFileStorage {
    /** @var array<string,string> */
    public array $files = [];
    public function write(string $key, string $contents): void { $this->files[$key] = $contents; }
    public function read(string $key): string { return $this->files[$key] ?? throw new RuntimeException('missing'); }
    public function delete(string $key): void { unset($this->files[$key]); }
    public function exists(string $key): bool { return isset($this->files[$key]); }
};
$repository = new class implements MediaAssetRepository {
    public ?StoredMediaAssetDto $created = null;
    public function create(StoredMediaAssetDto $asset): int { $this->created = $asset; return 42; }
    public function findById(int $id): ?MediaAssetRecord
    {
        if ($id !== 42 || $this->created === null) { return null; }
        $derivatives = [];
        foreach ($this->created->derivatives as $variant => $image) {
            $derivatives[] = new MediaAssetDerivativeRecord(
                $variant, $image->format, $image->width, $image->height,
                $this->created->derivativeKeys[$variant], strlen($image->contents),
            );
        }
        return new MediaAssetRecord(
            42, $this->created->originalFilename, $this->created->storageKey,
            $this->created->sourceMimeType, $this->created->sourceWidth,
            $this->created->sourceHeight, $this->created->sourceFileSizeBytes,
            new DateTimeImmutable('2026-11-25T00:00:00+00:00'), $derivatives,
        );
    }
    public function findLibrary(int $limit, int $offset): array { return []; }
    public function delete(int $id): void { $this->created = null; }
};
$transaction = new class implements Transaction {
    public function run(callable $operation): mixed { return $operation(); }
};
$allowed = new class implements MediaPermission {
    public function canView(): bool { return true; }
    public function canManage(): bool { return true; }
};
$service = new MediaAssetService($processor, $storage, $repository, $transaction, $allowed);

Assert::same(42, $service->upload($png, '../unsafe/name.png'));
Assert::same('name.png', $repository->created?->originalFilename);
Assert::same($png, $storage->read($repository->created?->storageKey ?? ''));
Assert::same(4, count($storage->files));
Assert::same(hash('sha256', $png), $repository->created?->sourceSha256);

$denied = new class implements MediaPermission {
    public function canView(): bool { return false; }
    public function canManage(): bool { return false; }
};
$deniedService = new MediaAssetService($processor, $storage, $repository, $transaction, $denied);
Assert::exception(static fn () => $deniedService->upload($png, 'image.png'), MediaAccessDeniedException::class);
Assert::exception(static fn () => $service->upload('not-an-image', 'bad.png'), InvalidArgumentException::class);

$service->delete(42);
Assert::same([], $storage->files);

echo "OK\n";
?>
--EXPECT--
OK
