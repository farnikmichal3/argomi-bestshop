<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Api;

use InvalidArgumentException;

/** Aktivace produktu je odmítnuta, dokud nesplňuje minimální publikační podmínky. */
final class ProductActivationRequirementsNotMet extends InvalidArgumentException
{
}
