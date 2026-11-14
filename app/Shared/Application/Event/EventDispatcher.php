<?php

declare(strict_types=1);

namespace App\Shared\Application\Event;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Aplikační rozhraní pro předání doménové události jejím posluchačům.
 *
 * Moduly závisí pouze na tomto kontraktu, nikoliv na konkrétním způsobu
 * doručování událostí. Implementaci lze později nahradit bez změny domény.
 */
interface EventDispatcher
{
    /**
     * Předá událost registrovaným posluchačům.
     *
     * @throws \Throwable Pokud některý posluchač při zpracování selže.
     */
    public function dispatch(DomainEvent $event): void;
}
