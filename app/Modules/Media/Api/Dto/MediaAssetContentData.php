<?php

declare(strict_types=1);

namespace App\Modules\Media\Api\Dto;

final readonly class MediaAssetContentData
{
    public function __construct(
        public string $mimeType,
        public string $contents,
    ) {}
}
