<?php

declare(strict_types=1);

namespace App\Modules\Media\Application;

use App\Modules\Media\Api\Dto\MediaAssetContentData;
use App\Modules\Media\Api\MediaAccessDeniedException;
use App\Modules\Media\Api\MediaAssetContentReader;
use App\Modules\Media\Api\MediaPermission;
use InvalidArgumentException;

final readonly class MediaAssetContentService implements MediaAssetContentReader
{
    private const VARIANTS = ['original', 'thumbnail', 'listing', 'detail'];

    public function __construct(
        private MediaAssetRepository $repository,
        private MediaFileStorage $storage,
        private MediaPermission $permission,
    ) {}

    public function read(int $id, string $variant = 'thumbnail'): ?MediaAssetContentData
    {
        if (!$this->permission->canView()) {
            throw new MediaAccessDeniedException('Přístup k obsahu médií byl odepřen.');
        }
        if ($id < 1 || !in_array($variant, self::VARIANTS, true)) {
            throw new InvalidArgumentException('Identifikátor nebo varianta média není platná.');
        }

        $asset = $this->repository->findById($id);
        if ($asset === null) {
            return null;
        }

        if ($variant === 'original') {
            if ($asset->sourceMimeType === null) {
                return null;
            }

            return new MediaAssetContentData(
                $asset->sourceMimeType,
                $this->storage->read($asset->storageKey),
            );
        }

        foreach ($asset->derivatives as $derivative) {
            if ($derivative->variant !== $variant) {
                continue;
            }

            $mimeType = match ($derivative->format) {
                'webp' => 'image/webp',
                'avif' => 'image/avif',
                default => throw new InvalidArgumentException('Formát derivátu média není podporován.'),
            };

            return new MediaAssetContentData(
                $mimeType,
                $this->storage->read($derivative->storageKey),
            );
        }

        return null;
    }
}
