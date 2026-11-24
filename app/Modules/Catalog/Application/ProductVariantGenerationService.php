<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\ProductVariantGenerator;
use App\Modules\Catalog\Domain\Variant\VariantCombinationHash;
use App\Shared\Application\Transaction\Transaction;
use InvalidArgumentException;

final readonly class ProductVariantGenerationService implements ProductVariantGenerator
{
    private const MAX_SELECTED_COMBINATIONS = 256;

    public function __construct(
        private ProductVariantGenerationRepository $repository,
        private Transaction $transaction,
        private CatalogPermission $permission,
    ) {}

    public function generate(int $productId, array $combinations): int
    {
        if (!$this->permission->canManage()) {
            throw new CatalogAccessDeniedException();
        }
        if ($productId < 1 || !array_is_list($combinations) || count($combinations) > self::MAX_SELECTED_COMBINATIONS) {
            throw new InvalidArgumentException('Požadavek na generování variant není platný.');
        }
        if ($combinations === []) {
            return 0;
        }

        $status = $this->repository->findProductStatus($productId);
        if ($status === null || $status === 'archived') {
            throw new InvalidArgumentException('Produkt neexistuje nebo je archivovaný.');
        }

        $axes = $this->repository->findAxisDefinitions($productId);
        if ($axes === []) {
            throw new InvalidArgumentException('Produkt nemá definované variantní osy.');
        }

        $normalized = $this->normalizeCombinations($combinations, $axes);

        return $this->transaction->run(function () use ($productId, $normalized): int {
            $status = $this->repository->findProductStatus($productId);
            if ($status === null || $status === 'archived') {
                throw new InvalidArgumentException('Produkt neexistuje nebo je archivovaný.');
            }

            $created = 0;
            $sortOrder = $this->repository->nextSortOrder($productId);
            foreach ($normalized as $combination) {
                $hash = VariantCombinationHash::create($combination);
                if ($this->repository->variantExists($productId, $hash)) {
                    continue;
                }

                // SKU je stabilní, produktově namespacované a ponechává prostor
                // pro pozdější ruční úpravu v navazujícím F5-18.
                $sku = sprintf('V%d-%s', $productId, substr($hash, 0, 40));
                $this->repository->createVariant($productId, $sku, $hash, $sortOrder);
                $variantId = $this->repository->findVariantIdByHash($productId, $hash);

                foreach ($combination as $code => $attribute) {
                    $axis = $this->repository->findAxisDefinitions($productId)[$code] ?? null;
                    if ($axis === null) {
                        throw new InvalidArgumentException('Varianta obsahuje neznámou osu.');
                    }
                    $this->repository->insertVariantAttributeValue(
                        $productId,
                        $code,
                        $axis['attributeId'],
                        $variantId,
                        $attribute['type'],
                        $attribute['value'],
                    );
                }

                $sortOrder++;
                $created++;
            }

            return $created;
        });
    }

    /**
     * @param list<array<string, array{type: string, value: string|bool}>> $combinations
     * @param array<string, array{attributeId: int, type: string}> $axes
     * @return list<array<string, array{type: string, value: string|bool}>>
     */
    private function normalizeCombinations(array $combinations, array $axes): array
    {
        $normalized = [];
        $seenHashes = [];

        foreach ($combinations as $combination) {
            if (!is_array($combination)) {
                throw new InvalidArgumentException('Kombinace varianty musí být pole os.');
            }

            $keys = array_keys($combination);
            if (array_filter($keys, 'is_string') !== $keys) {
                throw new InvalidArgumentException('Kódy os musí být textové.');
            }
            sort($keys, SORT_STRING);
            $expectedKeys = array_keys($axes);
            sort($expectedKeys, SORT_STRING);
            if ($keys !== $expectedKeys) {
                throw new InvalidArgumentException('Kombinace musí obsahovat právě všechny osy produktu.');
            }

            $values = [];
            foreach ($axes as $code => $axis) {
                $entry = $combination[$code] ?? null;
                if (
                    !is_array($entry)
                    || !isset($entry['type'])
                    || !array_key_exists('value', $entry)
                    || !is_string($entry['type'])
                    || $entry['type'] !== $axis['type']
                ) {
                    throw new InvalidArgumentException(sprintf('Hodnota osy %s nemá platný datový typ.', $code));
                }

                $value = $entry['value'];
                if ($axis['type'] === 'enum') {
                    if (!is_string($value) || !$this->repository->enumValueExists($axis['attributeId'], $value)) {
                        throw new InvalidArgumentException(sprintf('Enum hodnota osy %s neexistuje.', $code));
                    }
                } elseif ($axis['type'] === 'number') {
                    if (!is_string($value)) {
                        throw new InvalidArgumentException(sprintf('Číselná hodnota osy %s musí být desetinný řetězec.', $code));
                    }
                } elseif ($axis['type'] === 'text') {
                    if (!is_string($value) || mb_strlen($value) > 255) {
                        throw new InvalidArgumentException(sprintf('Textová hodnota osy %s není platná.', $code));
                    }
                } elseif ($axis['type'] === 'bool') {
                    if (!is_bool($value)) {
                        throw new InvalidArgumentException(sprintf('Logická hodnota osy %s musí být boolean.', $code));
                    }
                } else {
                    throw new InvalidArgumentException(sprintf('Osa %s má nepodporovaný datový typ.', $code));
                }

                $values[$code] = ['type' => $axis['type'], 'value' => $value];
            }

            $hash = VariantCombinationHash::create($values);
            if (isset($seenHashes[$hash])) {
                throw new InvalidArgumentException('Požadavek obsahuje duplicitní kombinaci varianty.');
            }
            $seenHashes[$hash] = true;
            $normalized[] = $values;
        }

        return $normalized;
    }
}
