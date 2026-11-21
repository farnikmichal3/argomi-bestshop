<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

/**
 * Server-side authorization boundary for catalog administration.
 */
interface CatalogPermission
{
    public function canView(): bool;

    public function canManage(): bool;
}
