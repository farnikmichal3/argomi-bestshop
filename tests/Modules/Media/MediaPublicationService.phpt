--TEST--
Media publication keeps assets private until explicitly published and revokes public reads
--FILE--
<?php

use App\Modules\Media\Api\MediaPermission;
use App\Modules\Media\Application\Dto\MediaAssetDerivativeRecord;
use App\Modules\Media\Application\Dto\MediaAssetRecord;
use App\Modules\Media\Application\MediaAssetRepository;
use App\Modules\Media\Application\MediaAssetPublicationService;
use App\Modules\Media\Application\MediaPublicAssetContentService;
use App\Modules\Media\Application\MediaFileStorage;
use App\Shared\Application\Transaction\Transaction;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$asset = new MediaAssetRecord(
    7,
    'photo.png',
    'media/original.bin',
    str_repeat('a', 64),
    'image/png',
    1,
    1,
    68,
    new DateTimeImmutable('2026-11-25T00:00:00+00:00'),
    [new MediaAssetDerivativeRecord('detail', 'webp', str_repeat('a', 64), str_repeat('b', 64), 1, 1, 'media/detail.webp', 10)],
);

$repository = new class($asset) implements MediaAssetRepository {
    public ?MediaAssetRecord $asset;
    public ?string $token = null;
    public bool $published = false;

    public function __construct(MediaAssetRecord $asset) { $this->asset = $asset; }
    public function create(\App\Modules\Media\Application\Dto\StoredMediaAssetDto $asset): int { return 1; }
    public function findById(int $id): ?MediaAssetRecord { return $id === 7 ? $this->asset : null; }
    public function findLibrary(int $limit, int $offset): array { return []; }
    public function delete(int $id): void { $this->asset = null; }
    public function publish(int $id, string $publicToken): void {
        $this->token = $publicToken;
        $this->published = true;
        $this->asset = new MediaAssetRecord(
            7, 'photo.png', 'media/original.bin', str_repeat('a', 64), 'image/png', 1, 1, 68,
            new DateTimeImmutable('2026-11-25T00:00:00+00:00'), $this->asset?->derivatives ?? [],
            $publicToken, new DateTimeImmutable('2026-11-25T00:00:00+00:00'),
        );
    }
    public function unpublish(int $id): void { $this->published = false; }
    public function findPublishedByToken(string $publicToken): ?MediaAssetRecord {
        return $this->published && $this->token === $publicToken ? $this->asset : null;
    }
};

$permission = new class implements MediaPermission {
    public bool $allowed = true;
    public function canView(): bool { return $this->allowed; }
    public function canManage(): bool { return $this->allowed; }
};
$transaction = new class implements Transaction {
    public function run(callable $operation): mixed { return $operation(); }
};
$storage = new class implements MediaFileStorage {
    public function write(string $key, string $contents): void {}
    public function read(string $key): string { return 'webp-content'; }
    public function delete(string $key): void {}
    public function exists(string $key): bool { return true; }
};

$publicReader = new MediaPublicAssetContentService($repository, $storage);
Assert::null($publicReader->read(str_repeat('a', 64), 'detail'));

$publisher = new MediaAssetPublicationService($repository, $permission, $transaction);
$token = $publisher->publish(7);
Assert::same(64, strlen($token));
Assert::same($token, $repository->token);
Assert::same('image/webp', $publicReader->read($token, 'detail')?->mimeType);
Assert::same('webp-content', $publicReader->read($token, 'detail')?->contents);

$publisher->unpublish(7);
Assert::null($publicReader->read($token, 'detail'));

$permission->allowed = false;
Assert::exception(static fn () => $publisher->publish(7), \App\Modules\Media\Api\MediaAccessDeniedException::class);
Assert::exception(static fn () => $publicReader->read('invalid'), InvalidArgumentException::class);

echo "OK\n";
?>
--EXPECT--
OK
