<?php

declare(strict_types=1);

namespace App\Modules\Media\Api\Dto;

use DateTimeImmutable;

final readonly class MediaAssetData
{
    /**
     * @param list<MediaAssetDerivativeData> $derivatives
     */
    public function __construct(
        public int $id,
        public string $originalFilename,
        public string $storageKey,
        public ?string $sourceSha256,
        public ?string $sourceMimeType,
        public ?int $sourceWidth,
        public ?int $sourceHeight,
        public ?int $sourceFileSizeBytes,
        public DateTimeImmutable $createdAt,
        public array $derivatives,
        public ?DateTimeImmutable $publishedAt = null,
        public ?string $publicToken = null,
    ) {}
}
