<?php

declare(strict_types=1);

namespace App\Modules\Media\Api;

use App\Modules\Media\Api\Dto\MediaAssetData;

interface MediaAssetReader
{
    public function find(int $id): ?MediaAssetData;

    /**
     * @return list<MediaAssetData>
     */
    public function findLibrary(int $limit = 48, int $offset = 0): array;
}
