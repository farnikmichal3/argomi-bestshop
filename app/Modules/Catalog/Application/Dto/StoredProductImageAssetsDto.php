<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

final readonly class StoredProductImageAssetsDto
{
    /**
     * @param array{thumbnail: string, listing: string, detail: string} $derivativePaths
     * @param array{
     *     thumbnail: array{format: string, mimeType: string, width: int, height: int, fileSizeBytes: int},
     *     listing: array{format: string, mimeType: string, width: int, height: int, fileSizeBytes: int},
     *     detail: array{format: string, mimeType: string, width: int, height: int, fileSizeBytes: int}
     * } $derivativeMetadata
     */
    public function __construct(
        public string $originalPath,
        public array $derivativePaths,
        public array $derivativeMetadata,
        public int $originalBytes,
        public string $sourceSha256,
        public string $sourceMimeType,
        public int $sourceWidth,
        public int $sourceHeight,
    ) {}
}
