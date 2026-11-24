<?php

declare(strict_types=1);

namespace App\Modules\Tax\Infrastructure\Persistence;

use App\Modules\Tax\Api\Dto\TaxClassOption;
use App\Modules\Tax\Api\TaxClassOptionReader;
use Nette\Database\Explorer;
use UnexpectedValueException;

final readonly class DatabaseTaxClassOptionReader implements TaxClassOptionReader
{
    public function __construct(private Explorer $database) {}

    public function findOptions(): array
    {
        $options = [];
        foreach ($this->database->table('tax_class')->order('code ASC')->fetchAll() as $row) {
            if (!is_string($row->code) || !is_string($row->name)) {
                throw new UnexpectedValueException('Daňová třída obsahuje neplatný kód nebo název.');
            }

            $id = $row->id;
            if (is_string($id) && ctype_digit($id)) {
                $id = filter_var($id, FILTER_VALIDATE_INT);
            }
            if (!is_int($id)) {
                throw new UnexpectedValueException('Daňová třída obsahuje neplatné ID.');
            }

            $options[] = new TaxClassOption($id, $row->code, $row->name);
        }
        return $options;
    }
}
