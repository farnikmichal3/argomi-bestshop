<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Api\Dto\ProductDto;
use App\Modules\Catalog\Api\Dto\ProductTranslationDto;
use App\Modules\Catalog\Application\ProductRepository;
use App\Shared\Domain\Locale\Locale;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use UnexpectedValueException;

/**
 * Databázová implementace čtecího kontraktu katalogu.
 *
 * Veškeré dotazy používají Nette Database Explorer. ActiveRow se mapuje
 * na typované DTO přímo v této třídě a nikdy neopouští persistence vrstvu.
 */
final class DatabaseProductRepository implements ProductRepository
{
    private readonly DateTimeZone $utc;

    public function __construct(
        private readonly Explorer $database,
    ) {
        $this->utc = new DateTimeZone('UTC');
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?ProductDto
    {
        $row = $this->database->table('product')
            ->where('id', $id)
            ->fetch();

        return $row === null ? null : $this->mapProduct($row);
    }

    /**
     * {@inheritDoc}
     */
    public function findByCode(string $code): ?ProductDto
    {
        $row = $this->database->table('product')
            ->where('code', $code)
            ->fetch();

        return $row === null ? null : $this->mapProduct($row);
    }

    /**
     * {@inheritDoc}
     */
    public function findTranslation(int $productId, Locale $locale): ?ProductTranslationDto
    {
        $row = $this->database->table('product_translation')
            ->where('product_id', $productId)
            ->where('locale', $locale->getCode())
            ->fetch();

        return $row === null ? null : $this->mapTranslation($row);
    }

    /**
     * {@inheritDoc}
     */
    public function findTranslations(int $productId): array
    {
        $rows = $this->database->table('product_translation')
            ->where('product_id', $productId)
            ->order('locale ASC')
            ->fetchAll();

        $translations = [];

        foreach ($rows as $row) {
            $translations[] = $this->mapTranslation($row);
        }

        return $translations;
    }

    /**
     * Převede produktový řádek na DTO a ověří očekávané databázové typy.
     *
     * Kontrola je záměrná i při existenci SQL omezení: poškozená nebo
     * neočekávaná data se mají projevit na hranici persistence.
     */
    private function mapProduct(ActiveRow $row): ProductDto
    {
        $status = $row->status;
        $code = $row->code;

        if (!is_string($status) || !is_string($code)) {
            throw new UnexpectedValueException('Databázový produkt obsahuje neplatný stav nebo kód.');
        }

        return new ProductDto(
            $this->mapInteger($row->id, 'product.id'),
            $code,
            $status,
            $this->mapInteger($row->tax_class_id, 'product.tax_class_id'),
            $this->mapDateTime($row->created_at, 'product.created_at'),
            $this->mapDateTime($row->updated_at, 'product.updated_at'),
        );
    }

    /**
     * Převede překladový řádek na DTO bez úniku ActiveRow.
     */
    private function mapTranslation(ActiveRow $row): ProductTranslationDto
    {
        $locale = $row->locale;
        $name = $row->name;
        $slug = $row->slug;

        if (!is_string($locale) || !is_string($name) || !is_string($slug)) {
            throw new UnexpectedValueException('Databázový překlad produktu obsahuje neplatný jazyk, název nebo slug.');
        }

        return new ProductTranslationDto(
            $this->mapInteger($row->id, 'product_translation.id'),
            $this->mapInteger($row->product_id, 'product_translation.product_id'),
            Locale::fromString($locale),
            $name,
            $slug,
            $this->mapNullableString($row->short_description, 'product_translation.short_description'),
            $this->mapNullableString($row->description, 'product_translation.description'),
            $this->mapNullableString($row->meta_title, 'product_translation.meta_title'),
            $this->mapNullableString($row->meta_description, 'product_translation.meta_description'),
        );
    }

    /**
     * Převede celočíselný databázový údaj včetně číselného řetězce na PHP int.
     *
     * BIGINT UNSIGNED může být v závislosti na ovladači vrácen jako int
     * nebo jako řetězec. Hodnoty mimo rozsah PHP int odmítneme místo tichého
     * přetečení, protože by změnilo identitu záznamu.
     */
    private function mapInteger(mixed $value, string $field): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            $integer = filter_var($value, FILTER_VALIDATE_INT);

            if ($integer !== false) {
                return $integer;
            }
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje platné PHP celé číslo.', $field));
    }

    /**
     * Převede volitelný textový sloupec a odmítne neočekávaný typ.
     */
    private function mapNullableString(mixed $value, string $field): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje text ani NULL.', $field));
    }

    /**
     * Převede DATETIME z databáze na neměnný okamžik v UTC.
     *
     * MySQL DATETIME neobsahuje informaci o časovém pásmu; projekt jej
     * ukládá v UTC, proto se řetězcová hodnota interpretuje výslovně jako UTC.
     *
     * @throws UnexpectedValueException Pokud datum nelze bezpečně převést.
     */
    private function mapDateTime(mixed $value, string $field): DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            // DATETIME nemá časové pásmo. Zachováme uložený čas na ciferníku
            // a znovu jej interpretujeme jako UTC; pouhé setTimezone() by
            // mohlo posunout hodnotu podle výchozí zóny PDO/PHP.
            return new DateTimeImmutable($value->format('Y-m-d H:i:s.u'), $this->utc);
        }

        if (is_string($value)) {
            try {
                return new DateTimeImmutable($value, $this->utc);
            } catch (Exception $exception) {
                throw new UnexpectedValueException(
                    sprintf('Databázové pole %s neobsahuje platné datum a čas.', $field),
                    previous: $exception,
                );
            }
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje platné datum a čas.', $field));
    }
}
