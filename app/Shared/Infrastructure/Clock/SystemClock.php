<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Clock;

use App\Shared\Domain\Clock\Clock;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Produkční hodiny čtoucí aktuální čas systému v UTC.
 */
final class SystemClock implements Clock
{
    /**
     * Vrátí aktuální okamžik s explicitním časovým pásmem UTC.
     *
     * Časové pásmo procesu ani operačního systému se tak nepromítne
     * do hodnot používaných v doméně a ukládaných do databáze.
     */
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
