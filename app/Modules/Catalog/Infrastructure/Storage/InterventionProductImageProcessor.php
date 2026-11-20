<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Storage;

use App\Modules\Catalog\Application\Dto\ProcessedProductImageDto;
use App\Modules\Catalog\Application\ProductImageProcessor;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
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
        $this->validateQuality($quality);
        $image = $this->decodeValidated($source);

        return $this->encode($image, $quality);
    }

    public function toVariants(string $source, int $quality = 82): array
    {
        $this->validateSource($source);
        $this->validateQuality($quality);

        return [
            'thumbnail' => $this->encode($this->decodeValidated($source)->cover(160, 160), $quality),
            'listing' => $this->encode($this->decodeValidated($source)->cover(480, 480), $quality),
            'detail' => $this->encode($this->decodeValidated($source)->scaleDown(1200, 1200), $quality),
        ];
    }

    private function decodeValidated(string $source): ImageInterface
    {
        $this->validateSource($source);

        try {
            $image = $this->manager->decode($source);
            $width = $image->width();
            $height = $image->height();

            if ($width < 1 || $height < 1 || ($width * $height) > self::MAX_PIXELS) {
                throw new InvalidArgumentException('Rozměry obrázku jsou neplatné nebo překračují limit 40 megapixelů.');
            }

            return $image;
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Zdrojová data nelze bezpečně dekódovat nebo převést na WebP.', previous: $exception);
        }
    }

    private function validateSource(string $source): void
    {
        if ($source === '' || strlen($source) > self::MAX_SOURCE_BYTES) {
            throw new InvalidArgumentException('Zdroj obrázku je prázdný nebo překračuje povolenou velikost 20 MB.');
        }
    }

    private function validateQuality(int $quality): void
    {
        if ($quality < self::MIN_QUALITY || $quality > self::MAX_QUALITY) {
            throw new InvalidArgumentException('Kvalita WebP musí být v rozsahu 1 až 100.');
        }
    }

    private function encode(ImageInterface $image, int $quality): ProcessedProductImageDto
    {
        $width = $image->width();
        $height = $image->height();
        $encoded = $image->encodeUsingFormat(Format::WEBP, quality: $quality, strip: true);

        return new ProcessedProductImageDto(
            (string) $encoded,
            'webp',
            'image/webp',
            'webp',
            $width,
            $height,
        );
    }
}
