<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use DomainException as NativeDomainException;

/**
 * Společný základ výjimek oznamujících porušení doménového pravidla.
 *
 * Konkrétní doménové výjimky z něj dědí, aby je bylo možné zachytit
 * jednotně bez závislosti na konkrétním hodnotovém objektu nebo modulu.
 * Zároveň zůstává zachována kompatibilita s vestavěnou PHP DomainException.
 */
abstract class DomainException extends NativeDomainException {}
