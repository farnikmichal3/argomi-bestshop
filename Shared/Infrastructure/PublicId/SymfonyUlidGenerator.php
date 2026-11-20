<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\PublicId;

use App\Shared\Domain\PublicId\PublicId;
use App\Shared\Domain\PublicId\PublicIdGenerator;
use Symfony\Component\Uid\Ulid;

/**
 * Produkční generátor veřejných ID pomocí komponenty Symfony UID.
 *
 * Závislost na Symfony zůstává v infrastruktuře; doménové objekty pracují
 * pouze s vlastním typem PublicId a standardním textovým zápisem ULID.
 */
final class SymfonyUlidGenerator implements PublicIdGenerator
{
    /**
     * Vygeneruje nový ULID a převede jej na doménový hodnotový objekt.
     */
    public function generate(): PublicId
    {
        return PublicId::fromString((new Ulid())->toBase32());
    }
}
