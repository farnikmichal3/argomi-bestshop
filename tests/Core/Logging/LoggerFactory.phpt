--TEST--
LoggerFactory writes structured JSON and sanitizes sensitive context before persistence
--FILE--
<?php

declare(strict_types=1);

use App\Core\Logging\LoggerFactory;
use App\Core\Logging\SensitiveDataProcessor;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

$directory = sys_get_temp_dir() . '/argomi-logger-' . bin2hex(random_bytes(8));
mkdir($directory, 0700, true);

try {
    $logger = LoggerFactory::createLogger(
        new SensitiveDataProcessor(),
        $directory . '/application.log',
        2,
        'DEBUG',
    );

    $logger->info('Checkout started', [
        'order_id' => 123,
        'payment_token' => 'must-not-be-written',
    ]);

    $files = glob($directory . '/application-*.log');
    Assert::count(1, $files);

    $contents = file_get_contents($files[0]);
    Assert::type('string', $contents);

    $record = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    Assert::same('Checkout started', $record['message']);
    Assert::same(123, $record['context']['order_id']);
    Assert::same('[REDACTED]', $record['context']['payment_token']);
} finally {
    foreach (glob($directory . '/*') ?: [] as $file) {
        unlink($file);
    }

    rmdir($directory);
}

echo "OK\n";
--EXPECT--
OK
