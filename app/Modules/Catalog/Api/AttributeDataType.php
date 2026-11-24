<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

/** Veřejný datový typ katalogového atributu. */
enum AttributeDataType: string
{
    case Text = 'text';
    case Number = 'number';
    case Enum = 'enum';
    case Bool = 'bool';
}
