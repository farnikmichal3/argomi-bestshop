<?php

declare(strict_types=1);

namespace App\Modules\Media\Application;

use App\Modules\Media\Application\Dto\MediaAssetRecord;
use App\Modules\Media\Application\Dto\StoredMediaAssetDto;

interface MediaAssetRepository
{
    public function create(StoredMediaAssetDto $asset): int;

    public function findById(int $id): ?MediaAssetRecord;

    /**
     * @return list<MediaAssetRecord>
     */
    public function findLibrary(int $limit, int $offset): array;

    public function delete(int $id): void;

    public function publish(int $id, string $publicToken): void;

    public function unpublish(int $id): void;

    public function findPublishedByToken(string $publicToken): ?MediaAssetRecord;
}
