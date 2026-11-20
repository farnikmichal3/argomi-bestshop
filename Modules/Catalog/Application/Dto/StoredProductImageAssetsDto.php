<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

final readonly class StoredProductImageAssetsDto
{
    /**
     * @param array{thumbnail: string, listing: string, detail: string} $derivativePaths
     */
    public function __construct(
        public string $originalPath,
        public array $derivativePaths,
        public int $originalBytes,
        public string $sourceSha256,
        public string $sourceMimeType,
        public int $sourceWidth,
        public int $sourceHeight,
    ) {}
}
