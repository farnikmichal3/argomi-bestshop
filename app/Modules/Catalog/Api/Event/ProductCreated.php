<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api\Event;

use App\Shared\Domain\Event\DomainEvent;
use InvalidArgumentException;

/** Signals that a catalog product was created. */
final readonly class ProductCreated implements DomainEvent
{
    public function __construct(
        public int $productId,
    ) {
        if ($productId < 1) {
            throw new InvalidArgumentException('ID produktu musí být kladné.');
        }
    }
}
