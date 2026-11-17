<?php

declare(strict_types=1);

namespace App\Modules\Translation\Application;

use App\Modules\Translation\Application\Dto\SystemMessageState;
use InvalidArgumentException;

/**
 * Určuje bezpečný stav překladu při změně výchozího systémového textu.
 *
 * Resolver je čistá aplikační logika; neprovádí databázové zápisy ani
 * invalidaci cache. Tyto kroky vlastní navazující importní use-case.
 */
final class SystemMessageStateResolver
{
    public function resolve(
        string $messageText,
        string $source,
        bool $isCustomized,
        ?string $defaultHash,
        bool $isOutdated,
        string $defaultText,
    ): SystemMessageState {
        if (!in_array($source, ['core', 'custom'], true)) {
            throw new InvalidArgumentException('Unknown system message source.');
        }

        // Vlastní klíče nejsou spravovány systémovým importem.
        if ($source === 'custom') {
            return new SystemMessageState(
                $messageText,
                $source,
                $isCustomized,
                $defaultHash,
                $isOutdated,
            );
        }

        // Bez známého předchozího hashe nelze bezpečně určit, zda se zdroj změnil.
        // Historické NULL hodnoty proto zůstávají beze změny.
        if ($defaultHash === null) {
            return new SystemMessageState(
                $messageText,
                $source,
                $isCustomized,
                null,
                $isOutdated,
            );
        }

        $currentDefaultHash = sha1($defaultText);

        if (!$isCustomized) {
            return new SystemMessageState(
                $defaultText,
                'core',
                false,
                $currentDefaultHash,
                false,
            );
        }

        return new SystemMessageState(
            $messageText,
            'core',
            true,
            $currentDefaultHash,
            !hash_equals($defaultHash, $currentDefaultHash),
        );
    }
}
