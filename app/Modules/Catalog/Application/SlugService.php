<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Domain\Slug\SlugTarget;
use App\Shared\Domain\Locale\Locale;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Transliterator;

/**
 * Generuje čitelné ASCII slugs a kontroluje jejich unikátnost v jazyce.
 *
 * Kolize řeší deterministickým číselným suffixem (-2, -3, ...). Kontrola
 * dostupnosti je předběžná; konečnou ochranu při souběžných zápisech tvoří
 * unikátní databázové indexy překladových tabulek.
 */
final class SlugService
{
    private readonly Transliterator $transliterator;

    public function __construct(
        private readonly SlugRepository $repository,
    ) {
        $transliterator = Transliterator::create('Any-Latin; Latin-ASCII; Lower()');

        if ($transliterator === null) {
            throw new LogicException('Nelze vytvořit ICU transliterator pro generování slugů.');
        }

        $this->transliterator = $transliterator;
    }

    /**
     * Vygeneruje první volný slug pro produkt nebo kategorii.
     *
     * Při úpravě existující entity lze předat její ID, aby její aktuální slug
     * nebyl považován za kolizi.
     */
    public function generate(
        SlugTarget $target,
        string $name,
        Locale $locale,
        ?int $excludeEntityId = null,
    ): string {
        self::assertEntityId($excludeEntityId);
        $base = $this->slugify($name);

        for ($suffix = 1; ; $suffix++) {
            $suffixText = $suffix === 1 ? '' : '-' . $suffix;
            $baseLength = 255 - strlen($suffixText);
            $candidateBase = rtrim(substr($base, 0, $baseLength), '-');

            if ($candidateBase === '') {
                throw new RuntimeException('Pro slug nezbývá po připojení suffixu žádný platný základ.');
            }

            $candidate = $candidateBase . $suffixText;

            if ($this->repository->isAvailable($target, $locale, $candidate, $excludeEntityId)) {
                return $candidate;
            }

            if ($suffix === PHP_INT_MAX) {
                throw new RuntimeException('Nepodařilo se nalézt volný slug.');
            }
        }
    }

    /**
     * Ověří dostupnost již vytvořeného slug v příslušném jazyce.
     */
    public function isAvailable(
        SlugTarget $target,
        string $slug,
        Locale $locale,
        ?int $excludeEntityId = null,
    ): bool {
        self::assertEntityId($excludeEntityId);

        if (strlen($slug) > 255 || preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $slug) !== 1) {
            throw new InvalidArgumentException('Slug musí obsahovat pouze malá ASCII písmena, číslice a jednoduché pomlčky a mít nejvýše 255 znaků.');
        }

        return $this->repository->isAvailable($target, $locale, $slug, $excludeEntityId);
    }

    private function slugify(string $name): string
    {
        if ($name === '' || preg_match('//u', $name) !== 1) {
            throw new InvalidArgumentException('Název pro generování slug musí být neprázdný platný UTF-8 text.');
        }

        $transliterated = $this->transliterator->transliterate(trim($name));

        if (!is_string($transliterated)) {
            throw new InvalidArgumentException('Název nelze převést na slug.');
        }

        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($transliterated));

        if (!is_string($slug)) {
            throw new InvalidArgumentException('Název nelze převést na slug.');
        }

        $slug = trim($slug, '-');

        if ($slug === '') {
            throw new InvalidArgumentException('Název neobsahuje žádné znaky použitelné pro slug.');
        }

        return substr($slug, 0, 255);
    }

    private static function assertEntityId(?int $entityId): void
    {
        if ($entityId !== null && $entityId < 1) {
            throw new InvalidArgumentException('ID vylučované entity musí být kladné.');
        }
    }
}
