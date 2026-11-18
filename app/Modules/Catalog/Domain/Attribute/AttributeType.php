<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Attribute;

/** Datový typ atributu podporovaný katalogovým schématem. */
enum AttributeType: string
{
    case Text = 'text';
    case Number = 'number';
    case Enum = 'enum';
    case Bool = 'bool';
}
