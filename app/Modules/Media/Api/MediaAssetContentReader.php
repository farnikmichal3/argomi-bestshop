<?php

declare(strict_types=1);

namespace App\Modules\Media\Api;

use App\Modules\Media\Api\Dto\MediaAssetContentData;

interface MediaAssetContentReader
{
    /**
     * @param 'original'|'thumbnail'|'listing'|'detail' $variant
     */
    public function read(int $id, string $variant = 'thumbnail'): ?MediaAssetContentData;
}
