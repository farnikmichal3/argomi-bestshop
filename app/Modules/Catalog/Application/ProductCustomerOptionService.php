<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\ProductCustomerOptionWriter;
use App\Modules\Catalog\Domain\Option\CustomerOptionType;
use App\Shared\Application\Transaction\Transaction;
use App\Shared\Domain\Locale\LocaleProvider;
use InvalidArgumentException;

final readonly class ProductCustomerOptionService implements ProductCustomerOptionWriter
{
    private const MAX_OPTIONS = 100;
    private const MAX_VALUES_PER_OPTION = 100;

    public function __construct(
        private ProductCustomerOptionRepository $repository,
        private LocaleProvider $localeProvider,
        private Transaction $transaction,
        private CatalogPermission $permission,
    ) {}

    public function save(int $productId, array $options): void
    {
        if (!$this->permission->canManage()) {
            throw new CatalogAccessDeniedException();
        }
        if ($productId < 1 || !$this->repository->productExists($productId)) {
            throw new InvalidArgumentException('Produkt nebyl nalezen.');
        }

        $normalized = $this->normalizeOptions($options);

        $this->transaction->run(function () use ($productId, $normalized): void {
            if (!$this->repository->productExists($productId)) {
                throw new InvalidArgumentException('Produkt nebyl nalezen.');
            }

            $currentOptions = $this->repository->findForProduct($productId);
            $currentById = [];
            foreach ($currentOptions as $current) {
                $currentById[$current['id']] = $current;
            }

            $submittedIds = [];
            foreach ($normalized as $option) {
                if ($option['id'] !== null) {
                    if (!isset($currentById[$option['id']])) {
                        throw new InvalidArgumentException('Zákaznická volba nepatří k upravovanému produktu.');
                    }
                    $submittedIds[$option['id']] = true;
                }
            }

            foreach ($currentOptions as $current) {
                if (isset($submittedIds[$current['id']])) {
                    continue;
                }
                if ($this->repository->optionUsedInCart($current['id'])) {
                    throw new InvalidArgumentException('Volbu nelze odstranit, protože je použita v aktivním košíku.');
                }
                $this->repository->deleteOption($productId, $current['id']);
            }

            foreach ($normalized as $option) {
                $current = $option['id'] === null ? null : $currentById[$option['id']];
                if ($current !== null && $current['type'] !== $option['type']->value
                    && $this->repository->optionUsedInCart($option['id'])) {
                    throw new InvalidArgumentException('Typ volby nelze změnit, protože je použita v aktivním košíku.');
                }

                $optionId = $option['id'] ?? $this->repository->createOption(
                    $productId,
                    $option['code'],
                    $option['type']->value,
                    $option['isRequired'],
                    $option['sortOrder'],
                );

                if ($option['id'] !== null) {
                    $this->repository->updateOption(
                        $productId,
                        $optionId,
                        $option['code'],
                        $option['type']->value,
                        $option['isRequired'],
                        $option['sortOrder'],
                    );
                }

                foreach ($option['translations'] as $locale => $name) {
                    $this->repository->saveOptionTranslation($optionId, $locale, $name);
                }

                $currentValues = $current['values'] ?? [];
                $currentValuesById = [];
                foreach ($currentValues as $currentValue) {
                    $currentValuesById[$currentValue['id']] = $currentValue;
                }

                $submittedValueIds = [];
                foreach ($option['values'] as $value) {
                    if ($value['id'] !== null) {
                        if (!isset($currentValuesById[$value['id']])) {
                            throw new InvalidArgumentException('Hodnota zákaznické volby nepatří k dané volbě.');
                        }
                        $submittedValueIds[$value['id']] = true;
                    }
                }

                foreach ($currentValues as $currentValue) {
                    if (isset($submittedValueIds[$currentValue['id']])) {
                        continue;
                    }
                    if ($this->repository->valueUsedInCart($currentValue['id'])) {
                        throw new InvalidArgumentException('Hodnotu nelze odstranit, protože je použita v aktivním košíku.');
                    }
                    $this->repository->deleteValue($optionId, $currentValue['id']);
                }

                foreach ($option['values'] as $value) {
                    $valueId = $value['id'] ?? $this->repository->createValue(
                        $optionId,
                        $value['code'],
                        $value['sortOrder'],
                    );
                    if ($value['id'] !== null) {
                        $this->repository->updateValue($optionId, $valueId, $value['code'], $value['sortOrder']);
                    }
                    foreach ($value['translations'] as $locale => $label) {
                        $this->repository->saveValueTranslation($valueId, $locale, $label);
                    }
                }
            }
        });
    }

    /**
     * @param array<mixed> $options
     * @return list<array{
     *     id:int|null,
     *     code:string,
     *     type:CustomerOptionType,
     *     isRequired:bool,
     *     sortOrder:int,
     *     translations:array<string,string>,
     *     values:list<array{id:int|null,code:string,sortOrder:int,translations:array<string,string>}>
     * }>
     */
    private function normalizeOptions(array $options): array
    {
        if (!array_is_list($options) || count($options) > self::MAX_OPTIONS) {
            throw new InvalidArgumentException('Seznam zákaznických voleb není platný.');
        }

        $normalized = [];
        $seenIds = [];
        $seenCodes = [];

        foreach ($options as $option) {
            if (!is_array($option)
                || !is_string($option['code'] ?? null)
                || !is_string($option['type'] ?? null)
                || !is_bool($option['isRequired'] ?? null)
                || !is_int($option['sortOrder'] ?? null)
                || !is_array($option['translations'] ?? null)
                || !is_array($option['values'] ?? null)) {
                throw new InvalidArgumentException('Konfigurace zákaznické volby obsahuje neplatná data.');
            }

            $id = $option['id'] ?? null;
            if ($id !== null && (!is_int($id) || $id < 1 || isset($seenIds[$id]))) {
                throw new InvalidArgumentException('ID zákaznické volby není platné nebo je duplicitní.');
            }
            if ($id !== null) {
                $seenIds[$id] = true;
            }

            $code = self::normalizeCode($option['code']);
            if (isset($seenCodes[$code])) {
                throw new InvalidArgumentException('Kód zákaznické volby musí být v rámci produktu jedinečný.');
            }
            $seenCodes[$code] = true;

            $type = CustomerOptionType::tryFrom($option['type']);
            if ($type === null) {
                throw new InvalidArgumentException('Typ zákaznické volby není podporován.');
            }
            if ($option['sortOrder'] < 0) {
                throw new InvalidArgumentException('Pořadí zákaznické volby nesmí být záporné.');
            }

            $translations = $this->normalizeTranslations($option['translations'], 'name');
            $rawValues = $option['values'];
            if (!array_is_list($rawValues) || count($rawValues) > self::MAX_VALUES_PER_OPTION) {
                throw new InvalidArgumentException('Seznam hodnot zákaznické volby není platný.');
            }
            if ($type === CustomerOptionType::Text && $rawValues !== []) {
                throw new InvalidArgumentException('Textová volba nesmí obsahovat číselník hodnot.');
            }

            $values = [];
            $valueIds = [];
            $valueCodes = [];
            foreach ($rawValues as $value) {
                if (!is_array($value)
                    || !is_string($value['code'] ?? null)
                    || !is_int($value['sortOrder'] ?? null)
                    || !is_array($value['translations'] ?? null)) {
                    throw new InvalidArgumentException('Hodnota zákaznické volby obsahuje neplatná data.');
                }

                $valueId = $value['id'] ?? null;
                if ($valueId !== null && (!is_int($valueId) || $valueId < 1 || isset($valueIds[$valueId]))) {
                    throw new InvalidArgumentException('ID hodnoty zákaznické volby není platné nebo je duplicitní.');
                }
                if ($valueId !== null) {
                    $valueIds[$valueId] = true;
                }

                $valueCode = self::normalizeCode($value['code']);
                if (isset($valueCodes[$valueCode])) {
                    throw new InvalidArgumentException('Kód hodnoty musí být v rámci volby jedinečný.');
                }
                $valueCodes[$valueCode] = true;

                if ($value['sortOrder'] < 0) {
                    throw new InvalidArgumentException('Pořadí hodnoty zákaznické volby nesmí být záporné.');
                }

                $values[] = [
                    'id' => $valueId,
                    'code' => $valueCode,
                    'sortOrder' => $value['sortOrder'],
                    'translations' => $this->normalizeTranslations($value['translations'], 'label'),
                ];
            }

            $normalized[] = [
                'id' => $id,
                'code' => $code,
                'type' => $type,
                'isRequired' => $option['isRequired'],
                'sortOrder' => $option['sortOrder'],
                'translations' => $translations,
                'values' => $values,
            ];
        }

        return $normalized;
    }

    /** @param array<mixed> $translations
     *  @return array<string,string>
     */
    private function normalizeTranslations(array $translations, string $field): array
    {
        $normalized = [];
        foreach ($this->localeProvider->getActiveLocales() as $locale) {
            $code = $locale->getCode();
            $value = $translations[$code] ?? null;
            if (!is_string($value)) {
                throw new InvalidArgumentException(sprintf('Chybí překlad pro aktivní jazyk %s.', $code));
            }
            $value = trim($value);
            if ($value === '' || mb_strlen($value) > 255 || preg_match('//u', $value) !== 1) {
                throw new InvalidArgumentException(sprintf('Překlad pro aktivní jazyk %s není platný.', $code));
            }
            $normalized[$code] = $value;
        }
        return $normalized;
    }

    private static function normalizeCode(string $code): string
    {
        $code = strtolower(trim($code));
        if (preg_match('/\A[a-z][a-z0-9_]{0,99}\z/D', $code) !== 1) {
            throw new InvalidArgumentException('Kód musí začínat malým ASCII písmenem a obsahovat pouze písmena, číslice a podtržítko.');
        }
        return $code;
    }
}
