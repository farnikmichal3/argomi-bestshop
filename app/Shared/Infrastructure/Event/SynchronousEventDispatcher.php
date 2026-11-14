<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Event;

use App\Shared\Application\Event\DomainEventListener;
use App\Shared\Application\Event\EventDispatcher;
use App\Shared\Domain\Event\DomainEvent;

/**
 * Synchronně předává doménové události posluchačům v určeném pořadí.
 *
 * Posluchače si ukládá do vlastního seznamu, aby bylo možné bezpečně
 * opakovaně procházet i původně předaný jednorázový iterable. Výjimky
 * posluchačů se záměrně nezachytávají: volající musí vědět, že zpracování
 * události nebylo dokončeno.
 */
final class SynchronousEventDispatcher implements EventDispatcher
{
    /** @var list<DomainEventListener> */
    private array $listeners = [];

    /**
     * @param iterable<DomainEventListener> $listeners Posluchači v pořadí volání.
     */
    public function __construct(iterable $listeners = [])
    {
        foreach ($listeners as $listener) {
            $this->listeners[] = $listener;
        }
    }

    public function dispatch(DomainEvent $event): void
    {
        foreach ($this->listeners as $listener) {
            if ($listener->supports($event)) {
                $listener->handle($event);
            }
        }
    }
}
