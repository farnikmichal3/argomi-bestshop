<?php

declare(strict_types=1);

namespace App\Modules\Media\Application\Dto;

final readonly class MediaAssetDerivativeRecord
{
    public function __construct(
        public string $variant,
        public string $format,
        public int $width,
        public int $height,
        public string $storageKey,
        public int $fileSizeBytes,
    ) {}
}
