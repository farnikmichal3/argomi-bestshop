<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\Dto\ProductImageDerivativeDto;
use App\Modules\Catalog\Application\Dto\ProductImageDto;
use App\Modules\Media\Api\Dto\MediaAssetData;
use App\Modules\Catalog\Application\ProductImageRepository;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

final class DatabaseProductImageRepository implements ProductImageRepository
{
    public function __construct(
        private readonly Explorer $database,
    ) {}

    public function findForProduct(int $productId, array $activeVariantIds): array
    {
        return $this->loadImages($productId, $activeVariantIds);
    }

    public function findForManagement(int $productId): array
    {
        return $this->loadImages($productId, null);
    }

    /**
     * @param list<int>|null $activeVariantIds
     * @return list<ProductImageDto>
     */
    private function loadImages(int $productId, ?array $activeVariantIds): array
    {
        $activeVariantIdSet = $activeVariantIds === null ? null : array_fill_keys($activeVariantIds, true);
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
                if ($activeVariantIdSet !== null && !isset($activeVariantIdSet[$variantId])) {
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
                $this->integer($row->media_asset_id, 'product_image.media_asset_id'),
                $row->product_variant_id === null
                    ? null
                    : $this->integer($row->product_variant_id, 'product_image.product_variant_id'),
                $this->string($row->file_path, 'product_image.file_path'),
                $this->nullableString($row->alt_text, 'product_image.alt_text'),
                $this->integer($row->sort_order, 'product_image.sort_order'),
                $this->integer($row->is_primary, 'product_image.is_primary') === 1,
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
        MediaAssetData $asset,
        bool $isPrimary = false,
    ): int {
        if ($productId < 1 || ($variantId !== null && $variantId < 1) || $sortOrder < 0) {
            throw new \InvalidArgumentException('Produkt, varianta a pořadí obrázku mají neplatnou hodnotu.');
        }

        $scope = $this->database->table('product_image')
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId);
        $hasPrimary = $scope->where('is_primary', 1)->fetch() !== null;
        $makePrimary = $isPrimary || !$hasPrimary;
        if ($makePrimary) {
            $this->database->table('product_image')
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->update(['is_primary' => 0]);
        }

        $image = $this->database->table('product_image')->insert([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'media_asset_id' => $asset->id,
            'file_path' => $asset->storageKey,
            'alt_text' => $altText,
            'sort_order' => $sortOrder,
            'is_primary' => $makePrimary ? 1 : 0,
            'source_sha256' => $asset->sourceSha256,
            'source_mime_type' => $asset->sourceMimeType,
            'source_width' => $asset->sourceWidth,
            'source_height' => $asset->sourceHeight,
            'source_file_size_bytes' => $asset->sourceFileSizeBytes,
        ]);

        if (!$image instanceof ActiveRow) {
            throw new UnexpectedValueException('Databáze nevrátila vložený obrázek produktu.');
        }

        $imageId = $this->integer($image->id, 'product_image.id');
        foreach ($asset->derivatives as $derivative) {
            $this->database->table('product_image_derivative')->insert([
                'product_image_id' => $imageId,
                'format' => $derivative->format,
                'source_sha256' => $derivative->sourceSha256,
                'recipe_sha256' => $derivative->recipeSha256,
                'width' => $derivative->width,
                'height' => $derivative->height,
                'file_path' => $derivative->storageKey,
                'file_size_bytes' => $derivative->fileSizeBytes,
            ]);
        }

        return $imageId;
    }

    public function updateMetadata(int $productId, int $imageId, ?string $altText, int $sortOrder): void
    {
        if ($productId < 1 || $imageId < 1 || $sortOrder < 0 || ($altText !== null && mb_strlen($altText) > 255)) {
            throw new \InvalidArgumentException('Metadata obrázku nejsou platná.');
        }

        $row = $this->database->table('product_image')
            ->where('product_id', $productId)
            ->where('id', $imageId)
            ->fetch();
        if (!$row instanceof ActiveRow) {
            throw new \InvalidArgumentException('Obrázek produktu nebyl nalezen.');
        }

        $this->database->table('product_image')
            ->where('id', $imageId)
            ->update(['alt_text' => $altText, 'sort_order' => $sortOrder]);
    }

    public function remove(int $productId, int $imageId): void
    {
        $row = $this->database->table('product_image')
            ->where('product_id', $productId)
            ->where('id', $imageId)
            ->fetch();
        if (!$row instanceof ActiveRow) {
            return;
        }

        $variantId = $row->product_variant_id;
        $wasPrimary = $this->integer($row->is_primary, 'product_image.is_primary') === 1;
        $this->database->table('product_image')->where('id', $imageId)->delete();

        if ($wasPrimary) {
            $next = $this->database->table('product_image')
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->order('sort_order ASC, id ASC')
                ->fetch();
            if ($next instanceof ActiveRow) {
                $this->database->table('product_image')
                    ->where('id', $this->integer($next->id, 'product_image.id'))
                    ->update(['is_primary' => 1]);
            }
        }
    }

    public function setPrimary(int $productId, int $imageId): void
    {
        $row = $this->database->table('product_image')
            ->where('product_id', $productId)
            ->where('id', $imageId)
            ->fetch();
        if (!$row instanceof ActiveRow) {
            throw new \InvalidArgumentException('Obrázek produktu nebyl nalezen.');
        }

        $variantId = $row->product_variant_id;
        $this->database->table('product_image')
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->update(['is_primary' => 0]);
        $this->database->table('product_image')
            ->where('id', $imageId)
            ->update(['is_primary' => 1]);
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
