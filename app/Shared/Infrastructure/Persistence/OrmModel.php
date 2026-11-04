<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use Nextras\Orm\Model\Model;

/**
 * Tady se budou postupně registrovat repozitáře jednotlivých modulů
 * např. @property-read \App\Modules\Catalog\Infrastructure\Persistence\ProductsRepository $products
 */
final class OrmModel extends Model
{
}