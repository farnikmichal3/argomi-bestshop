<?php

declare(strict_types=1);

namespace App\Core\Logging;

use InvalidArgumentException;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    public static function createLogger(
        SensitiveDataProcessor $processor,
        string $logFile,
        int $maxFiles,
        string $minimumLevel,
    ): LoggerInterface {
        if ($maxFiles < 1) {
            throw new InvalidArgumentException('The log retention must be at least one file.');
        }

        $level = match (strtoupper($minimumLevel)) {
            'DEBUG' => Level::Debug,
            'INFO' => Level::Info,
            'NOTICE' => Level::Notice,
            'WARNING' => Level::Warning,
            'ERROR' => Level::Error,
            'CRITICAL' => Level::Critical,
            'ALERT' => Level::Alert,
            'EMERGENCY' => Level::Emergency,
            default => throw new InvalidArgumentException('Invalid minimum log level.'),
        };
        $handler = new RotatingFileHandler($logFile, $maxFiles, $level, true, 0600);
        $handler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true));

        $logger = new Logger('argomi');
        $logger->pushProcessor($processor);
        $logger->pushHandler($handler);

        return $logger;
    }
}
