<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Clock;

use App\Shared\Domain\Clock\Clock;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Nastavitelné hodiny určené pro deterministické testování.
 *
 * Test může nastavit přesný okamžik a následně jej posouvat bez čekání
 * na skutečný čas. Třída se záměrně neregistruje jako produkční služba.
 */
final class FrozenClock implements Clock
{
    private DateTimeImmutable $currentTime;

    public function __construct(DateTimeImmutable $currentTime)
    {
        $this->setNow($currentTime);
    }

    /**
     * Vrátí aktuálně nastavený okamžik v UTC.
     */
    public function now(): DateTimeImmutable
    {
        return $this->currentTime;
    }

    /**
     * Nastaví nový okamžik; vstup se převede do UTC bez změny okamžiku.
     */
    public function setNow(DateTimeImmutable $currentTime): void
    {
        $this->currentTime = $currentTime->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * Posune testovací čas o zadaný interval.
     *
     * DateTimeImmutable zajistí, že se původní hodnota nezmění mimo hodiny.
     */
    public function advance(DateInterval $interval): void
    {
        $this->currentTime = $this->currentTime->add($interval);
    }
}
