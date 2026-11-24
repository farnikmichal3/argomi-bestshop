<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\ProductVariantWriter;
use App\Modules\Catalog\Domain\Variant\VariantEan;
use App\Shared\Application\Transaction\Transaction;
use InvalidArgumentException;

final readonly class ProductVariantWriteService implements ProductVariantWriter
{
    public function __construct(
        private ProductVariantWriteRepository $repository,
        private Transaction $transaction,
        private CatalogPermission $permission,
    ) {}

    public function update(
        int $variantId,
        string $sku,
        ?string $ean,
        ?int $weightG,
        bool $isDefault,
    ): void {
        if (!$this->permission->canManage()) {
            throw new CatalogAccessDeniedException();
        }
        if ($variantId < 1) {
            throw new InvalidArgumentException('ID varianty musí být kladné.');
        }

        $sku = trim($sku);
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]{0,63}\z/D', $sku) !== 1) {
            throw new InvalidArgumentException('SKU musí obsahovat 1 až 64 povolených ASCII znaků.');
        }

        $ean = $ean === null ? null : trim($ean);
        if ($ean === '') {
            $ean = null;
        }
        if ($ean !== null) {
            VariantEan::fromString($ean);
        }
        if ($weightG !== null && ($weightG < 0 || $weightG > 4294967295)) {
            throw new InvalidArgumentException('Hmotnost musí být celé číslo v rozsahu 0 až 4294967295 gramů.');
        }

        $this->transaction->run(function () use ($variantId, $sku, $ean, $weightG, $isDefault): void {
            if (!$this->repository->variantExists($variantId)) {
                throw new InvalidArgumentException('Varianta nebyla nalezena.');
            }
            if (!$this->repository->isSkuAvailable($sku, $variantId)) {
                throw new InvalidArgumentException('SKU již používá jiná varianta.');
            }
            if ($ean !== null && !$this->repository->isEanAvailable($ean, $variantId)) {
                throw new InvalidArgumentException('EAN již používá jiná varianta.');
            }

            $this->repository->updateVariant($variantId, $sku, $ean, $weightG);
            if ($isDefault) {
                if (!$this->repository->isVariantActive($variantId)) {
                    throw new InvalidArgumentException('Neaktivní varianta nemůže být výchozí.');
                }
                $productId = $this->repository->findProductId($variantId);
                if ($productId === null) {
                    throw new InvalidArgumentException('Produkt varianty nebyl nalezen.');
                }
                $this->repository->clearDefaultVariant($productId);
                $this->repository->setDefaultVariant($variantId);
            } else {
                $this->repository->unsetDefaultVariant($variantId);
            }
        });
    }
}
