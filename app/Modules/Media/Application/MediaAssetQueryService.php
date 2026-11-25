<?php

declare(strict_types=1);

namespace App\Modules\Media\Application;

use App\Modules\Media\Api\Dto\MediaAssetData;
use App\Modules\Media\Api\Dto\MediaAssetDerivativeData;
use App\Modules\Media\Api\MediaAccessDeniedException;
use App\Modules\Media\Api\MediaAssetReader;
use App\Modules\Media\Api\MediaPermission;

final readonly class MediaAssetQueryService implements MediaAssetReader
{
    public function __construct(
        private MediaAssetRepository $repository,
        private MediaPermission $permission,
    ) {}

    public function find(int $id): ?MediaAssetData
    {
        $this->assertCanView();
        if ($id < 1) {
            throw new \InvalidArgumentException('ID média musí být kladné.');
        }

        $asset = $this->repository->findById($id);

        return $asset === null ? null : $this->map($asset);
    }

    public function findLibrary(int $limit = 48, int $offset = 0): array
    {
        $this->assertCanView();
        if ($limit < 1 || $limit > 100 || $offset < 0) {
            throw new \InvalidArgumentException('Rozsah knihovny médií je neplatný.');
        }

        return array_map(
            fn($asset): MediaAssetData => $this->map($asset),
            $this->repository->findLibrary($limit, $offset),
        );
    }

    private function assertCanView(): void
    {
        if (!$this->permission->canView()) {
            throw new MediaAccessDeniedException('Přístup ke knihovně médií byl odepřen.');
        }
    }

    private function map(\App\Modules\Media\Application\Dto\MediaAssetRecord $asset): MediaAssetData
    {
        return new MediaAssetData(
            $asset->id,
            $asset->originalFilename,
            $asset->storageKey,
            $asset->sourceSha256,
            $asset->sourceMimeType,
            $asset->sourceWidth,
            $asset->sourceHeight,
            $asset->sourceFileSizeBytes,
            $asset->createdAt,
            array_map(
                static fn($derivative): MediaAssetDerivativeData => new MediaAssetDerivativeData(
                    $derivative->variant,
                    $derivative->format,
                    $derivative->sourceSha256,
                    $derivative->recipeSha256,
                    $derivative->width,
                    $derivative->height,
                    $derivative->storageKey,
                    $derivative->fileSizeBytes,
                ),
                $asset->derivatives,
            ),
        );
    }
}
