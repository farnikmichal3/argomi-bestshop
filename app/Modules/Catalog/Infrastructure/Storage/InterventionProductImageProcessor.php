<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Storage;

use App\Modules\Catalog\Application\Dto\ProcessedProductImageDto;
use App\Modules\Catalog\Application\ProductImageProcessor;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use Throwable;

final class InterventionProductImageProcessor implements ProductImageProcessor
{
    private const MAX_SOURCE_BYTES = 20_000_000;
    private const MAX_PIXELS = 40_000_000;
    private const MIN_QUALITY = 1;
    private const MAX_QUALITY = 100;

    private readonly ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(
            new Driver(),
            autoOrientation: true,
            decodeAnimation: false,
            backgroundColor: 'ffffff',
            strip: true,
        );
    }

    public function toWebp(string $source, int $quality = 82): ProcessedProductImageDto
    {
        if ($source === '' || strlen($source) > self::MAX_SOURCE_BYTES) {
            throw new InvalidArgumentException('Zdroj obrázku je prázdný nebo překračuje povolenou velikost 20 MB.');
        }

        if ($quality < self::MIN_QUALITY || $quality > self::MAX_QUALITY) {
            throw new InvalidArgumentException('Kvalita WebP musí být v rozsahu 1 až 100.');
        }

        try {
            $image = $this->manager->decode($source);
            $width = $image->width();
            $height = $image->height();

            if ($width < 1 || $height < 1 || ($width * $height) > self::MAX_PIXELS) {
                throw new InvalidArgumentException('Rozměry obrázku jsou neplatné nebo překračují limit 40 megapixelů.');
            }

            $encoded = $image->encodeUsingFormat(Format::WEBP, quality: $quality, strip: true);

            return new ProcessedProductImageDto(
                (string) $encoded,
                'webp',
                'image/webp',
                'webp',
                $width,
                $height,
            );
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Zdrojová data nelze bezpečně dekódovat nebo převést na WebP.', previous: $exception);
        }
    }
}
