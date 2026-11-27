<?php

declare(strict_types=1);

namespace App\\Modules\\Catalog\\Application;

use App\\Modules\\Catalog\\Domain\\Product\\ProductStatus;

interface ProductLifecycleRepository
{
    public function findStatus(int $productId): ?ProductStatus;

    public function archiveProduct(int $productId): void;

    /** Vytvoří nový koncept a zkopíruje pouze znovupoužitelná katalogová data. */
    public function cloneArchivedProduct(int $productId): int;
}
