<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Variant;

use InvalidArgumentException;

/**
 * Neměnný doménový model varianty produktu.
 *
 * Rozměry a hmotnost jsou uloženy v celých jednotkách podle SQL schématu:
 * gramy a milimetry. Kombinace atributů je identifikována SHA-256 hashem.
 */
final readonly class ProductVariant
{
    public function __construct(
        public int $id,
        public int $productId,
        public string $sku,
        public string $combinationHash,
        public bool $isDefault,
        public bool $isActive,
        public ?int $weightG,
        public ?int $lengthMm,
        public ?int $widthMm,
        public ?int $heightMm,
        public int $sortOrder,
    ) {
        if ($id < 1 || $productId < 1) {
            throw new InvalidArgumentException('ID varianty i produktu musí být kladná.');
        }

        if ($sku === '' || strlen($sku) > 64 || preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\z/D', $sku) !== 1) {
            throw new InvalidArgumentException('SKU musí obsahovat 1 až 64 ASCII písmen, číslic, teček, pomlček nebo podtržítek.');
        }

        if (preg_match('/\A[a-f0-9]{64}\z/D', $combinationHash) !== 1) {
            throw new InvalidArgumentException('Hash kombinace varianty musí být 64 malých hexadecimálních znaků SHA-256.');
        }

        foreach ([
            'hmotnost' => $weightG,
            'délka' => $lengthMm,
            'šířka' => $widthMm,
            'výška' => $heightMm,
        ] as $label => $value) {
            if ($value !== null && $value < 0) {
                throw new InvalidArgumentException(sprintf('Hodnota %s varianty nesmí být záporná.', $label));
            }
        }
    }
}
