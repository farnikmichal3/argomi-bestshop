<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Product;

use InvalidArgumentException;

/** Neměnný doménový model produktu bez vazby na databázovou vrstvu. */
final readonly class Product
{
    public function __construct(
        public int $id,
        public string $code,
        public ProductStatus $status,
        public int $taxClassId,
        public ProductType $type = ProductType::Physical,
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException('ID produktu musí být kladné.');
        }

        if ($taxClassId < 1) {
            throw new InvalidArgumentException('ID daňové třídy musí být kladné.');
        }

        if (preg_match('/\A[a-z0-9][a-z0-9_-]{0,99}\z/D', $code) !== 1) {
            throw new InvalidArgumentException('Kód produktu musí obsahovat 1 až 100 malých ASCII písmen, číslic, pomlček nebo podtržítek.');
        }
    }

    public function isPurchasable(): bool
    {
        return $this->status === ProductStatus::Active;
    }

    public function isArchived(): bool
    {
        return $this->status === ProductStatus::Archived;
    }
}
