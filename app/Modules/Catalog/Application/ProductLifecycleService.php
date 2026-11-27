<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Application;

use App\\Modules\\Catalog\\Api\\CatalogAccessDeniedException;
use App\\Modules\\Catalog\\Api\\CatalogPermission;
use App\\Modules\\Catalog\\Api\\ProductLifecycleWriter;
use App\\Modules\\Catalog\\Domain\\Product\\ProductStatus;
use App\\Shared\\Application\\Transaction\\Transaction;
use InvalidArgumentException;

final readonly class ProductLifecycleService implements ProductLifecycleWriter
{
    public function __construct(
        private ProductLifecycleRepository $repository,
        private Transaction $transaction,
        private CatalogPermission $permission,
    ) {}

    public function archive(int $productId): void
    {
        $this->assertCanManage();
        $this->assertProductId($productId);

        $this->transaction->run(function () use ($productId): void {
            $status = $this->repository->findStatus($productId);
            if ($status === null) {
                throw new InvalidArgumentException('Produkt nebyl nalezen.');
            }
            if ($status === ProductStatus::Archived) {
                throw new InvalidArgumentException('Produkt je již archivovaný.');
            }

            $this->repository->archiveProduct($productId);
        });
    }

    public function createCopyFromArchived(int $productId): int
    {
        $this->assertCanManage();
        $this->assertProductId($productId);

        return $this->transaction->run(function () use ($productId): int {
            if ($this->repository->findStatus($productId) !== ProductStatus::Archived) {
                throw new InvalidArgumentException('Kopii lze vytvořit pouze z archivovaného produktu.');
            }

            return $this->repository->cloneArchivedProduct($productId);
        });
    }

    private function assertCanManage(): void
    {
        if (!$this->permission->canManage()) {
            throw new CatalogAccessDeniedException();
        }
    }

    private function assertProductId(int $productId): void
    {
        if ($productId < 1) {
            throw new InvalidArgumentException('ID produktu musí být kladné.');
        }
    }
}
