<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Image;

use App\Modules\Media\Application\Dto\ProcessedMediaImageDto;
use App\Modules\Media\Application\MediaImageProcessor;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use InvalidArgumentException;
use Throwable;

final class InterventionMediaImageProcessor implements MediaImageProcessor
{
    private const MAX_SOURCE_BYTES = 20_000_000;
    private const MAX_PIXELS = 40_000_000;

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

    public function toVariants(string $source, int $quality = 82): array
    {
        if ($source === '' || strlen($source) > self::MAX_SOURCE_BYTES || $quality < 1 || $quality > 100) {
            throw new InvalidArgumentException('Zdrojový obrázek nebo kvalita zpracování jsou mimo povolené limity.');
        }

        $image = $this->decode($source);

        return [
            'thumbnail' => $this->encode($image->cover(160, 160), $quality),
            'listing' => $this->encode($image->cover(480, 480), $quality),
            'detail' => $this->encode($image->scaleDown(1200, 1200), $quality),
        ];
    }

    private function decode(string $source): ImageInterface
    {
        try {
            $image = $this->manager->decode($source);
            $width = $image->width();
            $height = $image->height();
            if ($width < 1 || $height < 1 || $width * $height > self::MAX_PIXELS) {
                throw new InvalidArgumentException('Obrázek překračuje limit 40 megapixelů.');
            }

            return $image;
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Obrázek nelze bezpečně dekódovat.', previous: $exception);
        }
    }

    private function encode(ImageInterface $image, int $quality): ProcessedMediaImageDto
    {
        $encoded = $image->encodeUsingFormat(Format::WEBP, quality: $quality, strip: true);

        return new ProcessedMediaImageDto(
            (string) $encoded,
            'webp',
            'image/webp',
            $image->width(),
            $image->height(),
        );
    }
}
