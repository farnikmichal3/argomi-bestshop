<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Application\Dto\StoredProductImageAssetsDto;
use App\Shared\Application\Transaction\Transaction;
use Throwable;

final class ProductImageRegistrationService
{
    public function __construct(
        private readonly ProductImageStorageService $imageStorage,
        private readonly ProductImageStorage $storage,
        private readonly ProductImageRepository $images,
        private readonly Transaction $transaction,
    ) {}

    public function register(
        int $productId,
        ?int $variantId,
        string $source,
        ?string $altText = null,
        int $sortOrder = 0,
        int $quality = 82,
    ): int {
        if ($productId < 1 || ($variantId !== null && $variantId < 1) || $sortOrder < 0) {
            throw new \InvalidArgumentException('Produkt, varianta a pořadí obrázku mají neplatnou hodnotu.');
        }

        $assets = $this->imageStorage->store($source, $quality);

        try {
            return $this->transaction->run(
                fn (): int => $this->images->createForProduct(
                    $productId,
                    $variantId,
                    $altText,
                    $sortOrder,
                    $assets,
                ),
            );
        } catch (Throwable $exception) {
            $this->removeStoredAssets($assets);
            throw $exception;
        }
    }

    private function removeStoredAssets(StoredProductImageAssetsDto $assets): void
    {
        foreach ([$assets->originalPath, ...array_values($assets->derivativePaths)] as $path) {
            try {
                $this->storage->delete($path);
            } catch (Throwable) {
                // Zachováme původní chybu databázové operace; osiřelé soubory
                // lze dohledat podle bezpečných storage klíčů při údržbě.
            }
        }
    }
}
