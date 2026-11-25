<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Persistence;

use App\Modules\Media\Application\Dto\MediaAssetDerivativeRecord;
use App\Modules\Media\Application\Dto\MediaAssetRecord;
use App\Modules\Media\Application\Dto\StoredMediaAssetDto;
use App\Modules\Media\Application\MediaAssetRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

final class DatabaseMediaAssetRepository implements MediaAssetRepository
{
    public function __construct(private readonly Explorer $database) {}

    public function create(StoredMediaAssetDto $asset): int
    {
        $row = $this->database->table('media_asset')->insert([
            'original_filename' => $asset->originalFilename,
            'storage_key' => $asset->storageKey,
            'source_sha256' => $asset->sourceSha256,
            'source_mime_type' => $asset->sourceMimeType,
            'source_width' => $asset->sourceWidth,
            'source_height' => $asset->sourceHeight,
            'source_file_size_bytes' => $asset->sourceFileSizeBytes,
        ]);
        if (!$row instanceof ActiveRow) {
            throw new UnexpectedValueException('Databáze nevrátila vložené médium.');
        }

        $id = $this->integer($row->id, 'media_asset.id');
        foreach ($asset->derivatives as $variant => $derivative) {
            $this->database->table('media_asset_derivative')->insert([
                'media_asset_id' => $id,
                'variant' => $variant,
                'format' => $derivative->format,
                'source_sha256' => $asset->sourceSha256,
                'recipe_sha256' => hash('sha256', $asset->recipeSha256 . ';variant=' . $variant),
                'width' => $derivative->width,
                'height' => $derivative->height,
                'storage_key' => $asset->derivativeKeys[$variant],
                'file_size_bytes' => strlen($derivative->contents),
            ]);
        }

        return $id;
    }

    public function findById(int $id): ?MediaAssetRecord
    {
        $row = $this->database->table('media_asset')->get($id);
        if (!$row instanceof ActiveRow) {
            return null;
        }

        return $this->mapRows([$row])[0];
    }

    public function findLibrary(int $limit, int $offset): array
    {
        if ($limit < 0 || $offset < 0) {
            throw new \InvalidArgumentException('Limit a offset knihovny médií nesmí být záporné.');
        }

        $rows = $this->database->table('media_asset')
            ->order('created_at DESC, id DESC')
            ->limit($limit, $offset)
            ->fetchAll();

        return $this->mapRows(array_values($rows));
    }

    public function delete(int $id): void
    {
        $this->database->table('media_asset')->where('id', $id)->delete();
    }

    public function publish(int $id, string $publicToken): void
    {
        if (preg_match('~^[a-f0-9]{64}$~D', $publicToken) !== 1) {
            throw new \InvalidArgumentException('Veřejný identifikátor média není platný.');
        }
        $this->database->table('media_asset')->where('id', $id)->update([
            'public_token' => $publicToken,
            'published_at' => new \DateTimeImmutable(),
        ]);
    }

    public function unpublish(int $id): void
    {
        $this->database->table('media_asset')->where('id', $id)->update(['published_at' => null]);
    }

    public function findPublishedByToken(string $publicToken): ?MediaAssetRecord
    {
        $row = $this->database->table('media_asset')->where('public_token', $publicToken)
            ->where('published_at IS NOT NULL')->fetch();
        if (!$row instanceof ActiveRow) {
            return null;
        }
        return $this->mapRows([$row])[0];
    }

    /**
     * @param list<ActiveRow> $rows
     * @return list<MediaAssetRecord>
     */
    private function mapRows(array $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = $this->integer($row->id, 'media_asset.id');
        }

        $derivativesByAsset = [];
        if ($ids !== []) {
            foreach ($this->database->table('media_asset_derivative')
                ->where('media_asset_id', $ids)
                ->order('media_asset_id ASC, variant ASC, format ASC')
                ->fetchAll() as $derivative) {
                $assetId = $this->integer($derivative->media_asset_id, 'media_asset_derivative.media_asset_id');
                $derivativesByAsset[$assetId][] = new MediaAssetDerivativeRecord(
                    $this->string($derivative->variant, 'media_asset_derivative.variant'),
                    $this->string($derivative->format, 'media_asset_derivative.format'),
                    $this->string($derivative->source_sha256, 'media_asset_derivative.source_sha256'),
                    $this->string($derivative->recipe_sha256, 'media_asset_derivative.recipe_sha256'),
                    $this->integer($derivative->width, 'media_asset_derivative.width'),
                    $this->integer($derivative->height, 'media_asset_derivative.height'),
                    $this->string($derivative->storage_key, 'media_asset_derivative.storage_key'),
                    $this->integer($derivative->file_size_bytes, 'media_asset_derivative.file_size_bytes'),
                );
            }
        }

        $assets = [];
        foreach ($rows as $row) {
            $id = $this->integer($row->id, 'media_asset.id');
            $createdAt = $row->created_at;
            if (!$createdAt instanceof DateTimeInterface) {
                if (!is_string($createdAt)) {
                    throw new UnexpectedValueException('media_asset.created_at nemá platný časový typ.');
                }
                $createdAt = new DateTimeImmutable($createdAt);
            }

            $assets[] = new MediaAssetRecord(
                $id,
                $this->string($row->original_filename, 'media_asset.original_filename'),
                $this->string($row->storage_key, 'media_asset.storage_key'),
                $this->nullableString($row->source_sha256, 'media_asset.source_sha256'),
                $this->nullableString($row->source_mime_type, 'media_asset.source_mime_type'),
                $this->nullableInteger($row->source_width, 'media_asset.source_width'),
                $this->nullableInteger($row->source_height, 'media_asset.source_height'),
                $this->nullableInteger($row->source_file_size_bytes, 'media_asset.source_file_size_bytes'),
                DateTimeImmutable::createFromInterface($createdAt),
                $derivativesByAsset[$id] ?? [],
                $this->nullableString($row->public_token, 'media_asset.public_token'),
                $row->published_at === null ? null : $this->toDateTime($row->published_at, 'media_asset.published_at'),
            );
        }

        return $assets;
    }

    private function toDateTime(mixed $value, string $field): DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }
        if (is_string($value)) {
            return new DateTimeImmutable($value);
        }
        throw new UnexpectedValueException(sprintf('Pole %s neobsahuje platný čas.', $field));
    }

    private function integer(mixed $value, string $field): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && ctype_digit($value)) {
            $result = filter_var($value, FILTER_VALIDATE_INT);
            if ($result !== false) {
                return $result;
            }
        }
        throw new UnexpectedValueException(sprintf('Pole %s neobsahuje platné celé číslo.', $field));
    }

    private function nullableInteger(mixed $value, string $field): ?int
    {
        return $value === null ? null : $this->integer($value, $field);
    }

    private function string(mixed $value, string $field): string
    {
        if (!is_string($value)) {
            throw new UnexpectedValueException(sprintf('Pole %s neobsahuje text.', $field));
        }
        return $value;
    }

    private function nullableString(mixed $value, string $field): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }
        throw new UnexpectedValueException(sprintf('Pole %s neobsahuje text ani NULL.', $field));
    }
}
