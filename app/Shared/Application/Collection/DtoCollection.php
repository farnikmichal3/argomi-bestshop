<?php

declare(strict_types=1);

namespace App\Shared\Application\Collection;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use OutOfBoundsException;
use Traversable;

/**
 * Neměnná typovaná kolekce homogenních datových přenosových objektů.
 *
 * @template T of object
 *
 * @implements IteratorAggregate<int, T>
 */
final readonly class DtoCollection implements IteratorAggregate, Countable
{
    /** @var list<T> */
    private array $items;

    /**
     * Vytvoří kolekci a ověří typ každé položky.
     *
     * Vstupní iterable se při konstrukci materializuje do vlastního seznamu.
     * Pozdější změny původního zdroje proto nemění členství ani pořadí kolekce.
     *
     * @param class-string<T> $itemType
     * @param iterable<mixed> $items
     *
     * @throws InvalidArgumentException Pokud očekávaný typ neexistuje nebo položka neodpovídá typu.
     */
    public function __construct(string $itemType, iterable $items)
    {
        if (!class_exists($itemType) && !interface_exists($itemType)) {
            throw new InvalidArgumentException(sprintf(
                'Expected DTO type "%s" is not an existing class or interface.',
                $itemType,
            ));
        }

        $validatedItems = [];

        foreach ($items as $item) {
            if (!$item instanceof $itemType) {
                throw new InvalidArgumentException(sprintf(
                    'Expected an instance of "%s", %s given.',
                    $itemType,
                    get_debug_type($item),
                ));
            }

            $validatedItems[] = $item;
        }

        $this->items = $validatedItems;
    }

    /**
     * Vrátí počet položek kolekce.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Ověří, zda kolekce neobsahuje žádnou položku.
     */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * Vrátí první položku nebo null, pokud je kolekce prázdná.
     *
     * @return T|null
     */
    public function first(): ?object
    {
        return $this->items[0] ?? null;
    }

    /**
     * Vrátí položku na nulově indexované pozici.
     *
     * @throws OutOfBoundsException Pokud index v kolekci neexistuje.
     *
     * @return T
     */
    public function get(int $index): object
    {
        if (!array_key_exists($index, $this->items)) {
            throw new OutOfBoundsException(sprintf(
                'DTO collection index %d is out of bounds.',
                $index,
            ));
        }

        return $this->items[$index];
    }

    /**
     * Vrátí nové běžné pole se zachováním pořadí položek.
     *
     * Změna struktury vráceného pole nemá vliv na kolekci.
     *
     * @return list<T>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Vrátí iterátor nad položkami kolekce.
     *
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
