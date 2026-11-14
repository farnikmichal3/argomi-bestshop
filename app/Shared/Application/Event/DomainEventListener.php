<?php

declare(strict_types=1);

namespace App\Shared\Application\Event;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Společný kontrakt posluchače doménových událostí.
 *
 * Posluchač nejprve určí, zda daný typ události zpracovává. Díky tomu
 * dispatcher nemusí znát konkrétní události ani jednotlivé moduly.
 */
interface DomainEventListener
{
    /**
     * Určí, zda posluchač umí zpracovat předanou událost.
     */
    public function supports(DomainEvent $event): bool;

    /**
     * Provede reakci na událost.
     *
     * Volá se pouze tehdy, pokud supports() vrátilo true.
     */
    public function handle(DomainEvent $event): void;
}
