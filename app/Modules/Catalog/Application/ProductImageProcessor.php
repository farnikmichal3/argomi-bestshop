<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Application\Dto\ProcessedProductImageDto;

interface ProductImageProcessor
{
    /**
     * Decode, normalize orientation, and encode source image to optimized WebP.
     *
     * @throws \InvalidArgumentException If source image is invalid or outside configured limits.
     */
    public function toWebp(string $source, int $quality = 82): ProcessedProductImageDto;

    /**
     * Create optimized square catalog derivatives and a detail image.
     *
     * @return array{thumbnail: ProcessedProductImageDto, listing: ProcessedProductImageDto, detail: ProcessedProductImageDto}
     *
     * @throws \InvalidArgumentException If source image is invalid or outside configured limits.
     */
    public function toVariants(string $source, int $quality = 82): array;
}
