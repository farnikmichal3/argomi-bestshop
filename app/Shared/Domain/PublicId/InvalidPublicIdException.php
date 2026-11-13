<?php

declare(strict_types=1);

namespace App\Shared\Domain\PublicId;

use InvalidArgumentException;

/**
 * Označuje pokus vytvořit veřejné ID z neplatného ULID zápisu.
 */
final class InvalidPublicIdException extends InvalidArgumentException {}
