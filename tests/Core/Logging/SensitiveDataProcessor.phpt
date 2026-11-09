--TEST--
SensitiveDataProcessor redacts sensitive values in nested context, strings, exceptions and extra data
--FILE--
<?php

declare(strict_types=1);

use App\Core\Logging\SensitiveDataProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$processor = new SensitiveDataProcessor();
$record = new LogRecord(
    new DateTimeImmutable(),
    'argomi',
    Level::Error,
    'Request failed',
    [
        'user_id' => 42,
        'credentials' => [
            'password' => 'do-not-log',
            'access_token' => 'token-value',
        ],
        'url' => '/reset?token=query-secret',
        'authorization_header' => 'Bearer header-secret',
        'exception' => new RuntimeException('exception contains private data'),
        'object' => new stdClass(),
    ],
    [
        'api_key' => 'extra-secret',
        'operation' => 'checkout',
    ],
);

$result = $processor($record);

Assert::same(42, $result->context['user_id']);
Assert::same('[REDACTED]', $result->context['credentials']['password']);
Assert::same('[REDACTED]', $result->context['credentials']['access_token']);
Assert::same('/reset?token=[REDACTED]', $result->context['url']);
Assert::same('Bearer [REDACTED]', $result->context['authorization_header']);
Assert::same(['class' => RuntimeException::class], $result->context['exception']);
Assert::same('[object stdClass]', $result->context['object']);
Assert::same('[REDACTED]', $result->extra['api_key']);
Assert::same('checkout', $result->extra['operation']);

echo "OK\n";
--EXPECT--
OK
