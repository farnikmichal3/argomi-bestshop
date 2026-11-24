<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

use RuntimeException;

final class AttributeInUseException extends RuntimeException
{
    public const ATTRIBUTE_USED = 'attribute_used';
    public const VALUE_USED = 'value_used';

    public function __construct(public readonly string $reason)
    {
        parent::__construct('Atribut nebo jeho hodnota je používána katalogovými daty.');
    }
}
