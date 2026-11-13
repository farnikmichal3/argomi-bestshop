<?php

declare(strict_types=1);

namespace App\Shared\Domain\Clock;

use DateTimeImmutable;

/**
 * Poskytuje aktuální okamžik nezávisle na systémových hodinách.
 *
 * Všechny implementace musí vracet časovou hodnotu v časovém pásmu UTC,
 * aby obchodní logika nebyla závislá na nastavení serveru ani uživatele.
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}
