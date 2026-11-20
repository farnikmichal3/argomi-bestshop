<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Dto;

final readonly class ProcessedProductImageDto
{
    public function __construct(
        public string $contents,
        public string $format,
        public string $mimeType,
        public string $extension,
        public int $width,
        public int $height,
    ) {}
}
