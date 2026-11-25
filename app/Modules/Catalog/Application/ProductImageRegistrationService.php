<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Media\Api\MediaAssetReader;
use App\Modules\Media\Api\MediaAssetWriter;
use App\Shared\Application\Transaction\Transaction;
use RuntimeException;
use Throwable;

final readonly class ProductImageRegistrationService
{
    public function __construct(
        private MediaAssetWriter $mediaWriter,
        private MediaAssetReader $mediaReader,
        private ProductImageRepository $images,
        private Transaction $transaction,
        private CatalogPermission $permission,
    ) {}

    public function register(
        int $productId,
        ?int $variantId,
        string $source,
        ?string $altText = null,
        int $sortOrder = 0,
        string $originalFilename = 'product-image',
    ): int {
        if (!$this->permission->canManage()) {
            throw new CatalogAccessDeniedException();
        }
        if ($productId < 1 || ($variantId !== null && $variantId < 1) || $sortOrder < 0) {
            throw new \InvalidArgumentException('Produkt, varianta a pořadí obrázku mají neplatnou hodnotu.');
        }

        $mediaId = $this->mediaWriter->upload($source, $originalFilename);
        try {
            $asset = $this->mediaReader->find($mediaId);
            if ($asset === null) {
                throw new RuntimeException('Nově uložené médium nebylo možné načíst.');
            }

            return $this->transaction->run(
                fn(): int => $this->images->createForProduct(
                    $productId,
                    $variantId,
                    $altText,
                    $sortOrder,
                    $asset,
                ),
            );
        } catch (Throwable $exception) {
            try {
                $this->mediaWriter->delete($mediaId);
            } catch (Throwable) {
                // Zachováme původní chybu; osiřelé médium lze dohledat podle ID.
            }
            throw $exception;
        }
    }
}
