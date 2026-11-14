<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use InvalidArgumentException as NativeInvalidArgumentException;

/**
 * Označuje vstup, který neodpovídá požadovanému formátu nebo pravidlům.
 *
 * Dědění z vestavěné InvalidArgumentException zachovává očekávanou sémantiku
 * a umožňuje stávajícímu kódu zachytávat neplatné argumenty beze změny.
 */
class InvalidInputException extends NativeInvalidArgumentException {}
