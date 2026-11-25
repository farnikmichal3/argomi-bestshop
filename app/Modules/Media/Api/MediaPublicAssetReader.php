<?php

declare(strict_types=1);

namespace App\Modules\Media\Api;

use App\Modules\Media\Api\Dto\MediaAssetContentData;

interface MediaPublicAssetReader
{
    /** @param 'thumbnail'|'listing'|'detail' $variant */
    public function read(string $publicToken, string $variant = 'detail'): ?MediaAssetContentData;
}
