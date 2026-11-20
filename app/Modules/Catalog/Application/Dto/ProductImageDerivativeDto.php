<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

final readonly class ProductImageDerivativeDto
{
    public function __construct(
        public string $format,
        public string $sourceSha256,
        public string $recipeSha256,
        public int $width,
        public int $height,
        public string $filePath,
        public int $fileSizeBytes,
    ) {}
}
