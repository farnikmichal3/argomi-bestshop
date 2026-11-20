<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\Dto\ProductImageDerivativeDto;
use App\Modules\Catalog\Application\Dto\ProductImageDto;
use App\Modules\Catalog\Application\Dto\StoredProductImageAssetsDto;
use App\Modules\Catalog\Application\ProductImageRepository;
use Nette\Database\Explorer;
use UnexpectedValueException;

final class DatabaseProductImageRepository implements ProductImageRepository
{
    public function __construct(
        private readonly Explorer $database,
    ) {}

    public function findForProduct(int $productId, array $activeVariantIds): array
    {
        $activeVariantIdSet = array_fill_keys($activeVariantIds, true);
        $rows = $this->database->table('product_image')
            ->where('product_id', $productId)
            ->order('sort_order ASC, id ASC')
            ->fetchAll();

        $selectedRows = [];
        $imageIds = [];
        foreach ($rows as $row) {
            $variantId = $row->product_variant_id;
            if ($variantId !== null) {
                $variantId = $this->integer($variantId, 'product_image.product_variant_id');
                if (!isset($activeVariantIdSet[$variantId])) {
                    continue;
                }
            }

            $selectedRows[] = $row;
            $imageIds[] = $this->integer($row->id, 'product_image.id');
        }

        $derivativesByImage = [];
        if ($imageIds !== []) {
            foreach (
                $this->database->table('product_image_derivative')
                    ->where('product_image_id', $imageIds)
                    ->order('width ASC, height ASC, format ASC, id ASC')
                    ->fetchAll() as $row
            ) {
                $imageId = $this->integer($row->product_image_id, 'product_image_derivative.product_image_id');
                $derivativesByImage[$imageId][] = new ProductImageDerivativeDto(
                    $this->string($row->format, 'product_image_derivative.format'),
                    $this->string($row->source_sha256, 'product_image_derivative.source_sha256'),
                    $this->string($row->recipe_sha256, 'product_image_derivative.recipe_sha256'),
                    $this->integer($row->width, 'product_image_derivative.width'),
                    $this->integer($row->height, 'product_image_derivative.height'),
                    $this->string($row->file_path, 'product_image_derivative.file_path'),
                    $this->integer($row->file_size_bytes, 'product_image_derivative.file_size_bytes'),
                );
            }
        }

        $images = [];
        foreach ($selectedRows as $row) {
            $id = $this->integer($row->id, 'product_image.id');
            $images[] = new ProductImageDto(
                $id,
                $row->product_variant_id === null
                    ? null
                    : $this->integer($row->product_variant_id, 'product_image.product_variant_id'),
                $this->string($row->file_path, 'product_image.file_path'),
                $this->nullableString($row->alt_text, 'product_image.alt_text'),
                $this->integer($row->sort_order, 'product_image.sort_order'),
                $this->nullableString($row->source_sha256, 'product_image.source_sha256'),
                $this->nullableString($row->source_mime_type, 'product_image.source_mime_type'),
                $this->nullableInteger($row->source_width, 'product_image.source_width'),
                $this->nullableInteger($row->source_height, 'product_image.source_height'),
                $this->nullableInteger($row->source_file_size_bytes, 'product_image.source_file_size_bytes'),
                $derivativesByImage[$id] ?? [],
            );
        }

        return $images;
    }

    public function createForProduct(
        int $productId,
        ?int $variantId,
        ?string $altText,
        int $sortOrder,
        StoredProductImageAssetsDto $assets,
    ): int {
        if ($productId < 1 || ($variantId !== null && $variantId < 1) || $sortOrder < 0) {
            throw new \InvalidArgumentException('Produkt, varianta a pořadí obrázku mají neplatnou hodnotu.');
        }

        $image = $this->database->table('product_image')->insert([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'file_path' => $assets->originalPath,
            'alt_text' => $altText,
            'sort_order' => $sortOrder,
            'source_sha256' => $assets->sourceSha256,
            'source_mime_type' => $assets->sourceMimeType,
            'source_width' => $assets->sourceWidth,
            'source_height' => $assets->sourceHeight,
            'source_file_size_bytes' => $assets->originalBytes,
        ]);

        $imageId = $this->integer($image->id, 'product_image.id');
        foreach (['thumbnail', 'listing', 'detail'] as $kind) {
            $metadata = $assets->derivativeMetadata[$kind];
            $this->database->table('product_image_derivative')->insert([
                'product_image_id' => $imageId,
                'format' => $metadata['format'],
                'source_sha256' => $assets->sourceSha256,
                'recipe_sha256' => hash('sha256', $assets->recipeSha256 . ';variant=' . $kind),
                'width' => $metadata['width'],
                'height' => $metadata['height'],
                'file_path' => $assets->derivativePaths[$kind],
                'file_size_bytes' => $metadata['fileSizeBytes'],
            ]);
        }

        return $imageId;
    }

    private function integer(mixed $value, string $field): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/\\A-?[0-9]+\\z/D', $value) === 1) {
            $validated = filter_var($value, FILTER_VALIDATE_INT);
            if ($validated !== false) {
                return $validated;
            }
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje platné celé číslo.', $field));
    }

    private function nullableInteger(mixed $value, string $field): ?int
    {
        return $value === null ? null : $this->integer($value, $field);
    }

    private function string(mixed $value, string $field): string
    {
        if (!is_string($value)) {
            throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje text.', $field));
        }

        return $value;
    }

    private function nullableString(mixed $value, string $field): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje text ani NULL.', $field));
    }
}
