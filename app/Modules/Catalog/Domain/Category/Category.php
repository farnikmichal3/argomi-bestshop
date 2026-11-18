<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Category;

use InvalidArgumentException;

/**
 * Neměnný doménový model kategorie katalogu.
 *
 * Hierarchické cykly a existence rodiče ověřuje aplikační služba nad stromem;
 * tento objekt hlídá lokální invarianty jedné kategorie.
 */
final readonly class Category
{
    public function __construct(
        public int $id,
        public ?int $parentId,
        public string $code,
        public int $sortOrder,
        public bool $isActive,
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException('ID kategorie musí být kladné.');
        }

        if ($parentId !== null && $parentId < 1) {
            throw new InvalidArgumentException('ID rodičovské kategorie musí být kladné.');
        }

        if ($parentId === $id) {
            throw new InvalidArgumentException('Kategorie nemůže být sama sobě rodičem.');
        }

        if (preg_match('/\A[a-z0-9][a-z0-9_-]{0,99}\z/D', $code) !== 1) {
            throw new InvalidArgumentException('Kód kategorie musí obsahovat 1 až 100 malých ASCII písmen, číslic, pomlček nebo podtržítek.');
        }
    }
}
