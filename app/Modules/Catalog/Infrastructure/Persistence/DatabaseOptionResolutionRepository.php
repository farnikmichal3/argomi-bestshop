<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\Dto\CustomerOptionDefinitionDto;
use App\Modules\Catalog\Application\OptionResolutionRepository;
use App\Modules\Catalog\Domain\Option\CustomerOption;
use App\Modules\Catalog\Domain\Option\CustomerOptionType;
use Nette\Database\Explorer;
use UnexpectedValueException;

/**
 * Dávkové načtení definic zákaznických voleb a jejich povolených hodnot.
 */
final readonly class DatabaseOptionResolutionRepository implements OptionResolutionRepository
{
    public function __construct(
        private Explorer $database,
    ) {}

    public function findActiveOptionDefinitions(int $productId): ?array
    {
        $product = $this->database->table('product')
            ->where('id', $productId)
            ->where('status', 'active')
            ->fetch();

        if ($product === null) {
            return null;
        }

        $optionRows = $this->database->table('product_option')
            ->where('product_id', $productId)
            ->order('sort_order ASC, id ASC')
            ->fetchAll();

        if ($optionRows === []) {
            return [];
        }

        $optionIds = [];
        foreach ($optionRows as $row) {
            $optionIds[] = $this->integer($row->id, 'product_option.id');
        }

        $valueIdsByOption = [];
        foreach (
            $this->database->table('product_option_value')
                ->where('product_option_id', $optionIds)
                ->order('sort_order ASC, id ASC')
                ->fetchAll() as $row
        ) {
            $optionId = $this->integer($row->product_option_id, 'product_option_value.product_option_id');
            $valueIdsByOption[$optionId][] = $this->integer($row->id, 'product_option_value.id');
        }

        $definitions = [];
        foreach ($optionRows as $row) {
            $optionId = $this->integer($row->id, 'product_option.id');
            $typeValue = $row->type;

            if (!is_string($typeValue) || ($type = CustomerOptionType::tryFrom($typeValue)) === null) {
                throw new UnexpectedValueException('Databázová zákaznická volba obsahuje neznámý typ.');
            }

            $definitions[] = new CustomerOptionDefinitionDto(
                new CustomerOption(
                    $optionId,
                    $this->integer($row->product_id, 'product_option.product_id'),
                    $this->string($row->code, 'product_option.code'),
                    $type,
                    $this->boolean($row->is_required, 'product_option.is_required'),
                    $this->integer($row->sort_order, 'product_option.sort_order'),
                ),
                $valueIdsByOption[$optionId] ?? [],
            );
        }

        return $definitions;
    }

    private function integer(mixed $value, string $field): int
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

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje platné celé číslo.', $field));
    }

    private function string(mixed $value, string $field): string
    {
        if (!is_string($value)) {
            throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje text.', $field));
        }

        return $value;
    }

    private function boolean(mixed $value, string $field): bool
    {
        if ($value === true || $value === 1 || $value === '1') {
            return true;
        }

        if ($value === false || $value === 0 || $value === '0') {
            return false;
        }

        throw new UnexpectedValueException(sprintf('Databázové pole %s neobsahuje platný boolean.', $field));
    }
}
