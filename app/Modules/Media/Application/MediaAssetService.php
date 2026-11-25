<?php

declare(strict_types=1);

namespace App\Modules\Media\Application;

use App\Modules\Media\Api\MediaAccessDeniedException;
use App\Modules\Media\Api\MediaAssetWriter;
use App\Modules\Media\Api\MediaPermission;
use App\Modules\Media\Application\Dto\StoredMediaAssetDto;
use App\Shared\Application\Transaction\Transaction;
use InvalidArgumentException;
use Throwable;

final readonly class MediaAssetService implements MediaAssetWriter
{
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];

    public function __construct(
        private MediaImageProcessor $processor,
        private MediaFileStorage $storage,
        private MediaAssetRepository $repository,
        private Transaction $transaction,
        private MediaPermission $permission,
    ) {}

    public function upload(string $source, string $originalFilename): int
    {
        $this->assertCanManage();
        if ($source === '' || strlen($source) > 20_000_000) {
            throw new InvalidArgumentException('Obrázek je prázdný nebo překračuje limit 20 MB.');
        }

        $filename = $this->normalizeFilename($originalFilename);
        $imageInfo = getimagesizefromstring($source);
        if ($imageInfo === false || !in_array($imageInfo['mime'], self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('Nahraný soubor není podporovaný rastrový obrázek.');
        }

        $derivatives = $this->processor->toVariants($source);
        $base = 'media/' . bin2hex(random_bytes(16));
        $storageKey = $base . '/original.bin';
        $derivativeKeys = [
            'thumbnail' => $base . '/thumbnail.webp',
            'listing' => $base . '/listing.webp',
            'detail' => $base . '/detail.webp',
        ];
        $contents = [
            $storageKey => $source,
            $derivativeKeys['thumbnail'] => $derivatives['thumbnail']->contents,
            $derivativeKeys['listing'] => $derivatives['listing']->contents,
            $derivativeKeys['detail'] => $derivatives['detail']->contents,
        ];

        $written = [];
        try {
            foreach ($contents as $key => $bytes) {
                $this->storage->write($key, $bytes);
                $written[] = $key;
            }
        } catch (Throwable $exception) {
            $this->cleanup($written);
            throw new \RuntimeException('Nepodařilo se uložit kompletní sadu mediálních souborů.', previous: $exception);
        }

        $recipe = 'media-derivatives:v1:thumbnail=160x160-cover;listing=480x480-cover;detail=1200x1200-scale-down;webp;quality=82';
        $asset = new StoredMediaAssetDto(
            $filename,
            $storageKey,
            $derivativeKeys,
            $derivatives,
            hash('sha256', $source),
            $imageInfo['mime'],
            $imageInfo[0],
            $imageInfo[1],
            strlen($source),
            hash('sha256', $recipe),
        );

        try {
            return $this->transaction->run(fn(): int => $this->repository->create($asset));
        } catch (Throwable $exception) {
            $this->cleanup($written);
            throw $exception;
        }
    }

    public function delete(int $id): void
    {
        $this->assertCanManage();
        if ($id < 1) {
            throw new InvalidArgumentException('ID média musí být kladné.');
        }

        $asset = $this->repository->findById($id);
        if ($asset === null) {
            return;
        }

        $this->transaction->run(fn() => $this->repository->delete($id));
        $keys = [$asset->storageKey];
        foreach ($asset->derivatives as $derivative) {
            $keys[] = $derivative->storageKey;
        }
        $this->cleanup($keys);
    }

    private function assertCanManage(): void
    {
        if (!$this->permission->canManage()) {
            throw new MediaAccessDeniedException('Správa médií byla odepřena.');
        }
    }

    private function normalizeFilename(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', trim($filename)));
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename);
        $filename = trim($filename);
        if ($filename === '') {
            throw new InvalidArgumentException('Název nahrávaného souboru není platný.');
        }

        return mb_substr($filename, 0, 255);
    }

    /**
     * @param list<string> $keys
     */
    private function cleanup(array $keys): void
    {
        foreach ($keys as $key) {
            try {
                $this->storage->delete($key);
            } catch (Throwable) {
                // The storage key remains an orphan candidate for maintenance cleanup.
            }
        }
    }
}
