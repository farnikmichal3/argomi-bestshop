<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

/**
 * Označuje událost, která popisuje něco, co se již v doméně stalo.
 *
 * Rozhraní záměrně nepředepisuje společná metadata. Konkrétní událost
 * nese pouze údaje potřebné k popisu dané skutečnosti a zůstává nezávislá
 * na aplikačním dispatcheru, frameworku i infrastruktuře.
 */
interface DomainEvent {}
