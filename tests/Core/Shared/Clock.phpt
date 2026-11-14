--TEST--
Clock implementations always expose UTC and FrozenClock advances deterministically
--FILE--
<?php

declare(strict_types=1);

use App\Shared\Domain\Clock\Clock;
use App\Shared\Infrastructure\Clock\FrozenClock;
use App\Shared\Infrastructure\Clock\SystemClock;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$utc = new DateTimeZone('UTC');
$input = new DateTimeImmutable('2026-11-14 12:30:00', new DateTimeZone('Europe/Berlin'));
$frozen = new FrozenClock($input);

Assert::type(Clock::class, $frozen);
Assert::same('2026-11-14 11:30:00', $frozen->now()->format('Y-m-d H:i:s'));
Assert::same('UTC', $frozen->now()->getTimezone()->getName());
Assert::same('2026-11-14 12:30:00', $input->format('Y-m-d H:i:s'));

$replacement = new DateTimeImmutable('2026-11-15 08:00:00', new DateTimeZone('America/New_York'));
$frozen->setNow($replacement);
Assert::same('2026-11-15 13:00:00', $frozen->now()->format('Y-m-d H:i:s'));
Assert::same('UTC', $frozen->now()->getTimezone()->getName());

$frozen->advance(new DateInterval('PT90M'));
Assert::same('2026-11-15 14:30:00', $frozen->now()->format('Y-m-d H:i:s'));
$frozen->advance(new DateInterval('P1D'));
Assert::same('2026-11-16 14:30:00', $frozen->now()->format('Y-m-d H:i:s'));

$systemClock = new SystemClock();
Assert::type(Clock::class, $systemClock);
$before = new DateTimeImmutable('now', $utc);
$now = $systemClock->now();
$after = new DateTimeImmutable('now', $utc);

Assert::same('UTC', $now->getTimezone()->getName());
Assert::true($now >= $before);
Assert::true($now <= $after);

echo "OK\n";
--EXPECT--
OK
