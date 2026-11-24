<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\ProductConfigurationWriter;
use App\Shared\Application\Transaction\Transaction;
use InvalidArgumentException;

final readonly class ProductConfigurationService implements ProductConfigurationWriter
{
    public function __construct(
        private ProductConfigurationRepository $repository,
        private Transaction $transaction,
        private CatalogPermission $permission,
    ) {}

    public function save(int $productId, array $attributes, array $variantAxisIds): void
    {
        if (!$this->permission->canManage()) {
            throw new CatalogAccessDeniedException();
        }
        if ($productId < 1 || !$this->repository->productExists($productId)) {
            throw new InvalidArgumentException('Produkt nebyl nalezen.');
        }

        $normalizedAttributes = $this->normalizeAttributes($attributes);
        $normalizedAxes = $this->normalizeAxisIds($variantAxisIds);

        $definitions = [];
        foreach ($normalizedAttributes as $entry) {
            $definition = $this->repository->findAttributeDefinition($entry['attributeId']);
            if ($definition === null) {
                throw new InvalidArgumentException('Přiřazený produktový atribut neexistuje.');
            }
            $definitions[$entry['attributeId']] = $definition;
        }
        foreach ($normalizedAxes as $attributeId) {
            $definition = $this->repository->findAttributeDefinition($attributeId);
            if ($definition === null) {
                throw new InvalidArgumentException('Vybraná osa variant neexistuje.');
            }
            $definitions[$attributeId] = $definition;
        }

        $typedAttributes = [];
        foreach ($normalizedAttributes as $entry) {
            $definition = $definitions[$entry['attributeId']];
            $value = $this->normalizeValue($definition['type'], $entry['value'], $entry['attributeId']);
            if ($definition['type'] === 'enum') {
                if (!is_int($value)) {
                    throw new InvalidArgumentException('Enum hodnota atributu musí být celé číslo.');
                }
                if (!$this->repository->enumValueBelongsToAttribute($entry['attributeId'], $value)) {
                    throw new InvalidArgumentException('Vybraná enum hodnota nepatří k danému atributu.');
                }
            }
            $typedAttributes[] = [
                'attributeId' => $entry['attributeId'],
                'type' => $definition['type'],
                'value' => $value,
            ];
        }

        foreach ($normalizedAxes as $attributeId) {
            if (!in_array($definitions[$attributeId]['type'], ['text', 'number', 'enum', 'bool'], true)) {
                throw new InvalidArgumentException('Datový typ osy variant není podporován.');
            }
        }

        $this->transaction->run(function () use ($productId, $typedAttributes, $normalizedAxes): void {
            $hasVariants = $this->repository->hasVariants($productId);
            $currentAxes = $this->repository->findVariantAxisIds($productId);
            $currentSet = $currentAxes;
            $requestedSet = $normalizedAxes;
            sort($currentSet, SORT_NUMERIC);
            sort($requestedSet, SORT_NUMERIC);

            if ($hasVariants && $currentSet !== $requestedSet) {
                throw new InvalidArgumentException(
                    'Sadu os nelze změnit, dokud produkt obsahuje varianty. Je možné pouze změnit jejich pořadí.',
                );
            }

            $this->repository->replaceProductAttributes($productId, $typedAttributes);
            $this->repository->replaceVariantAxes($productId, $normalizedAxes);
        });
    }

    /**
     * @param array<mixed> $attributes
     * @return list<array{attributeId:int, value:int|string|bool}>
     */
    private function normalizeAttributes(array $attributes): array
    {
        $normalized = [];
        $seen = [];
        foreach ($attributes as $entry) {
            if (!is_array($entry) || !isset($entry['attributeId']) || !is_int($entry['attributeId'])
                || $entry['attributeId'] < 1 || !array_key_exists('value', $entry)
                || (!is_int($entry['value']) && !is_string($entry['value']) && !is_bool($entry['value']))
                || isset($seen[$entry['attributeId']])) {
                throw new InvalidArgumentException('Přiřazení produktových atributů obsahuje neplatná nebo duplicitní data.');
            }
            $seen[$entry['attributeId']] = true;
            $normalized[] = ['attributeId' => $entry['attributeId'], 'value' => $entry['value']];
        }
        return $normalized;
    }

    /** @param array<mixed> $attributeIds
     *  @return list<int>
     */
    private function normalizeAxisIds(array $attributeIds): array
    {
        $normalized = [];
        $seen = [];
        foreach ($attributeIds as $attributeId) {
            if (!is_int($attributeId) || $attributeId < 1 || isset($seen[$attributeId])) {
                throw new InvalidArgumentException('Seznam os variant obsahuje neplatné nebo duplicitní ID.');
            }
            $seen[$attributeId] = true;
            $normalized[] = $attributeId;
        }
        return $normalized;
    }

    private function normalizeValue(string $type, int|string|bool $value, int $attributeId): int|string|bool
    {
        return match ($type) {
            'enum' => is_int($value) && $value > 0
                ? $value
                : throw new InvalidArgumentException(sprintf('Enum hodnota atributu %d musí být platné ID.', $attributeId)),
            'number' => is_string($value) && preg_match('/\A-?(?:0|[1-9][0-9]{0,13})(?:\.[0-9]{1,6})?\z/D', $value) === 1
                ? $value
                : throw new InvalidArgumentException(sprintf('Číselná hodnota atributu %d musí být desetinný řetězec s nejvýše 6 místy.', $attributeId)),
            'text' => is_string($value) && preg_match('//u', $value) === 1 && mb_strlen($value) <= 255
                ? trim($value)
                : throw new InvalidArgumentException(sprintf('Textová hodnota atributu %d není platná.', $attributeId)),
            'bool' => is_bool($value)
                ? $value
                : throw new InvalidArgumentException(sprintf('Logická hodnota atributu %d musí být boolean.', $attributeId)),
            default => throw new InvalidArgumentException('Atribut má nepodporovaný datový typ.'),
        };
    }
}
