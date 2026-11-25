<?php

declare(strict_types=1);

namespace App\Modules\Media\Application;

use App\Modules\Media\Application\Dto\ProcessedMediaImageDto;

interface MediaImageProcessor
{
    /**
     * @return array{thumbnail: ProcessedMediaImageDto, listing: ProcessedMediaImageDto, detail: ProcessedMediaImageDto}
     */
    public function toVariants(string $source, int $quality = 82): array;
}
