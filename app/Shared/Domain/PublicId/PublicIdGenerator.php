<?php

declare(strict_types=1);

namespace App\Shared\Domain\PublicId;

/**
 * Definuje generování veřejných identifikátorů bez vazby na konkrétní knihovnu.
 */
interface PublicIdGenerator
{
    public function generate(): PublicId;
}
