--TEST--
Catalog product lifecycle events are immutable typed domain events
--FILE--
<?php

use App\Modules\Catalog\Api\Event\ProductCreated;
use App\Modules\Catalog\Api\Event\ProductDeleted;
use App\Modules\Catalog\Api\Event\ProductUpdated;
use App\Shared\Domain\Event\DomainEvent;
use Tester\Assert;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';

foreach ([ProductCreated::class, ProductUpdated::class, ProductDeleted::class] as $eventClass) {
    $event = new $eventClass(12);
    Assert::true($event instanceof DomainEvent);
    Assert::same(12, $event->productId);
    Assert::exception(
        static fn () => new $eventClass(0),
        InvalidArgumentException::class,
    );
}

Assert::exception(
    static fn () => new ProductCreated(-1),
    InvalidArgumentException::class,
);

echo "OK\n";
?>
--EXPECT--
OK
