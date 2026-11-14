<?php

declare(strict_types=1);

namespace App\Shared\Domain\PublicId;

use App\Shared\Domain\Exception\InvalidInputException;

/**
 * Označuje pokus vytvořit veřejné ID z neplatného ULID zápisu.
 *
 * Zachovává sémantiku neplatného argumentu prostřednictvím InvalidInputException.
 */
final class InvalidPublicIdException extends InvalidInputException {}
