<?php

declare(strict_types=1);

namespace App\Modules\Media\Application\Dto;

final readonly class StoredMediaAssetDto
{
    /**
     * @param array{thumbnail: string, listing: string, detail: string} $derivativeKeys
     * @param array{
     *     thumbnail: ProcessedMediaImageDto,
     *     listing: ProcessedMediaImageDto,
     *     detail: ProcessedMediaImageDto
     * } $derivatives
     */
    public function __construct(
        public string $originalFilename,
        public string $storageKey,
        public array $derivativeKeys,
        public array $derivatives,
        public string $sourceSha256,
        public string $sourceMimeType,
        public int $sourceWidth,
        public int $sourceHeight,
        public int $sourceFileSizeBytes,
        public string $recipeSha256,
    ) {}
}
