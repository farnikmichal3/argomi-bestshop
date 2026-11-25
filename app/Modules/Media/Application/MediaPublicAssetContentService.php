<?php

declare(strict_types=1);

namespace App\Modules\Media\Application;

use App\Modules\Media\Api\Dto\MediaAssetContentData;
use App\Modules\Media\Api\MediaPublicAssetReader;
use InvalidArgumentException;

final readonly class MediaPublicAssetContentService implements MediaPublicAssetReader
{
    public function __construct(
        private MediaAssetRepository $repository,
        private MediaFileStorage $storage,
    ) {}

    public function read(string $publicToken, string $variant = 'detail'): ?MediaAssetContentData
    {
        if (preg_match('/\\A[a-f0-9]{64}\\z/D', $publicToken) !== 1) {
            throw new InvalidArgumentException('Veřejný identifikátor média není platný.');
        }

        $asset = $this->repository->findPublishedByToken($publicToken);
        if ($asset === null) {
            return null;
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

            return new MediaAssetContentData($mimeType, $this->storage->read($derivative->storageKey));
        }

        return null;
    }
}
