<?php

declare(strict_types=1);

namespace App\Shared\Domain\Locale;

use Stringable;

/**
 * Neměnný identifikátor jazyka a případné regionální varianty.
 *
 * Objekt neřeší dostupnost jazyka v obchodě ani vyhledávání překladů.
 * Přijímá běžnou podmnožinu BCP 47: jazyk, volitelný script a volitelný region.
 */
final readonly class Locale implements Stringable
{
    private function __construct(
        private string $code,
    ) {}

    /**
     * Vytvoří Locale a sjednotí zápis jazykového identifikátoru.
     *
     * Jazyk se převádí na malá písmena, script na počáteční velké písmeno
     * a region na velká písmena. Neznámé části ani rozšíření se nepřijímají.
     *
     * @throws InvalidLocaleException Pokud hodnota neodpovídá podporované syntaxi.
     */
    public static function fromString(string $value): self
    {
        if (preg_match('/\A([a-z]{2,3})(?:-([a-z]{4}))?(?:-([a-z]{2}|[0-9]{3}))?\z/iD', $value, $matches) !== 1) {
            throw InvalidLocaleException::forValue($value);
        }

        $language = strtolower($matches[1]);
        $script = isset($matches[2]) && $matches[2] !== ''
            ? '-' . ucfirst(strtolower($matches[2]))
            : '';
        $region = isset($matches[3]) && $matches[3] !== ''
            ? '-' . (ctype_digit($matches[3]) ? $matches[3] : strtoupper($matches[3]))
            : '';

        return new self($language . $script . $region);
    }

    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Vrátí základní jazyk bez scriptu a regionální varianty.
     */
    public function getLanguage(): string
    {
        return explode('-', $this->code, 2)[0];
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
