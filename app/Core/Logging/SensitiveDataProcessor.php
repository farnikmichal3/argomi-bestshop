<?php

declare(strict_types=1);

namespace App\Core\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Throwable;

final class SensitiveDataProcessor implements ProcessorInterface
{
    private const REDACTED = '[REDACTED]';

    private const SENSITIVE_KEY_PATTERN = '/(?:pass(?:word)?|passwd|pwd|token|secret|authorization|cookie|session|api.?key|private.?key|card.?number|cvv|cvc|security.?code)/i';

    /**
     * @param array<string|int, mixed> $context
     * @return array<string|int, mixed>
     */
    private function sanitizeArray(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            if (is_string($key) && preg_match(self::SENSITIVE_KEY_PATTERN, $key) === 1) {
                $sanitized[$key] = self::REDACTED;
                continue;
            }

            $sanitized[$key] = $this->sanitizeValue($value);
        }

        return $sanitized;
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->sanitizeArray($value);
        }

        if ($value instanceof Throwable) {
            $exception = [
                'class' => $value::class,
            ];

            if ($value->getCode() !== 0) {
                $exception['code'] = $value->getCode();
            }

            return $exception;
        }

        if (is_object($value)) {
            return '[object ' . $value::class . ']';
        }

        if (is_resource($value)) {
            return '[resource]';
        }

        if (is_string($value)) {
            return $this->sanitizeString($value);
        }

        return $value;
    }

    private function sanitizeString(string $value): string
    {
        $value = preg_replace(
            '/\b(Bearer)\s+[A-Za-z0-9._~+\/-]+=*/i',
            '$1 ' . self::REDACTED,
            $value,
        ) ?? $value;

        return preg_replace(
            '/\b(password|passwd|pwd|token|access[_-]?token|refresh[_-]?token|api[_-]?key|client[_-]?secret|secret|authorization|cookie|set-cookie)\s*([=:])\s*("[^"]*"|\'[^\']*\'|[^\s&,;]+)/i',
            '$1$2' . self::REDACTED,
            $value,
        ) ?? $value;
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: $this->sanitizeString($record->message),
            context: $this->sanitizeArray($record->context),
            extra: $this->sanitizeArray($record->extra),
        );
    }
}
