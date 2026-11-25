<?php

declare(strict_types=1);

namespace App\Modules\Media\Application\Dto;

final readonly class ProcessedMediaImageDto
{
    public function __construct(
        public string $contents,
        public string $format,
        public string $mimeType,
        public int $width,
        public int $height,
    ) {}
}
