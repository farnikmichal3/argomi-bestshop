<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Dto;

use DateTimeImmutable;

/**
 * Neměnný přenosový objekt základních údajů katalogového produktu.
 *
 * DTO záměrně neobsahuje ActiveRow ani vazby na databázovou knihovnu.
 * Časové údaje jsou při mapování převedeny do UTC.
 */
final readonly class ProductDto
{
    public function __construct(
        public int $id,
        public string $code,
        public string $status,
        public int $taxClassId,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}
}
