<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Application\Dto\StoredProductImageAssetsDto;
use RuntimeException;
use Throwable;

final class ProductImageStorageService
{
    public function __construct(
        private readonly ProductImageProcessor $processor,
        private readonly ProductImageStorage $storage,
    ) {}

    public function store(string $source, int $quality = 82): StoredProductImageAssetsDto
    {
        $processed = $this->processor->toVariants($source, $quality);
        $sourceInfo = getimagesizefromstring($source);
        if ($sourceInfo === false) {
            throw new RuntimeException('Nepodařilo se načíst metadata originálního obrázku.');
        }

        $base = 'products/' . bin2hex(random_bytes(16));
        $paths = [
            'original' => $base . '/original.bin',
            'thumbnail' => $base . '/thumbnail.webp',
            'listing' => $base . '/listing.webp',
            'detail' => $base . '/detail.webp',
        ];
        $contents = [
            'original' => $source,
            'thumbnail' => $processed['thumbnail']->contents,
            'listing' => $processed['listing']->contents,
            'detail' => $processed['detail']->contents,
        ];

        $written = [];
        try {
            foreach ($contents as $type => $bytes) {
                $this->storage->write($paths[$type], $bytes);
                $written[] = $paths[$type];
            }
        } catch (Throwable $exception) {
            foreach ($written as $path) {
                try {
                    $this->storage->delete($path);
                } catch (Throwable) {
                    // Best-effort rollback; preserve the original write exception.
                }
            }
            throw new RuntimeException('Nepodařilo se uložit kompletní sadu obrázků produktu.', previous: $exception);
        }

        return new StoredProductImageAssetsDto(
            $paths['original'],
            [
                'thumbnail' => $paths['thumbnail'],
                'listing' => $paths['listing'],
                'detail' => $paths['detail'],
            ],
            strlen($source),
            hash('sha256', $source),
            $sourceInfo['mime'],
            $sourceInfo[0],
            $sourceInfo[1],
        );
    }
}
