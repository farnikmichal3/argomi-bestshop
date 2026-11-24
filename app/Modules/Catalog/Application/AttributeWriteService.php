<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Api\AttributeInUseException;
use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Domain\Attribute\AttributeType;
use App\Shared\Application\Transaction\Transaction;
use App\Shared\Domain\Locale\LocaleProvider;
use InvalidArgumentException;

final readonly class AttributeWriteService
{
    public function __construct(
        private AttributeWriteRepository $repository,
        private LocaleProvider $localeProvider,
        private Transaction $transaction,
        private CatalogPermission $permission,
    ) {}

    public function create(string $code, AttributeType $type, ?int $unitId, bool $isFilterable, int $sortOrder, array $translations): int
    {
        $this->assertCanManage();
        $code = self::normalizeCode($code);
        self::assertTypeAndUnit($type, $unitId);
        self::assertSortOrder($sortOrder);
        $localized = $this->normalizeTranslations($translations, 'name');
        if (!$this->repository->isCodeAvailable($code)) {
            throw new InvalidArgumentException('Kód atributu již existuje.');
        }
        if ($unitId !== null && !$this->repository->unitExists($unitId)) {
            throw new InvalidArgumentException('Měrná jednotka neexistuje nebo není aktivní.');
        }

        return $this->transaction->run(function () use ($code, $type, $unitId, $isFilterable, $sortOrder, $localized): int {
            $id = $this->repository->createAttribute($code, $type, $unitId, $isFilterable, $sortOrder);
            foreach ($localized as $locale => $name) {
                $this->repository->saveAttributeTranslation($id, $locale, $name);
            }
            return $id;
        });
    }

    public function update(int $attributeId, string $code, AttributeType $type, ?int $unitId, bool $isFilterable, int $sortOrder, array $translations): void
    {
        $this->assertCanManage();
        if ($attributeId < 1 || !$this->repository->attributeExists($attributeId)) {
            throw new InvalidArgumentException('Atribut nebyl nalezen.');
        }
        $code = self::normalizeCode($code);
        self::assertTypeAndUnit($type, $unitId);
        self::assertSortOrder($sortOrder);
        $localized = $this->normalizeTranslations($translations, 'name');
        if (!$this->repository->isCodeAvailable($code, $attributeId)) {
            throw new InvalidArgumentException('Kód atributu již existuje.');
        }
        if ($unitId !== null && !$this->repository->unitExists($unitId)) {
            throw new InvalidArgumentException('Měrná jednotka neexistuje nebo není aktivní.');
        }

        $currentType = $this->repository->findAttributeType($attributeId);
        if ($currentType === null) {
            throw new InvalidArgumentException('Atribut nebyl nalezen.');
        }
        if ($currentType !== $type && ($this->repository->hasAttributeAxes($attributeId) || $this->repository->hasAttributeValues($attributeId))) {
            throw new InvalidArgumentException('Typ atributu nelze změnit, dokud obsahuje hodnoty nebo je použit jako osa variant.');
        }

        $this->transaction->run(function () use ($attributeId, $code, $type, $unitId, $isFilterable, $sortOrder, $localized): void {
            $this->repository->updateAttribute($attributeId, $code, $type, $unitId, $isFilterable, $sortOrder);
            foreach ($localized as $locale => $name) {
                $this->repository->saveAttributeTranslation($attributeId, $locale, $name);
            }
        });
    }

    public function delete(int $attributeId): void
    {
        $this->assertCanManage();
        if ($attributeId < 1 || !$this->repository->attributeExists($attributeId)) {
            throw new InvalidArgumentException('Atribut nebyl nalezen.');
        }
        $this->transaction->run(function () use ($attributeId): void {
            if ($this->repository->hasAttributeAxes($attributeId)) {
                throw new AttributeInUseException(AttributeInUseException::ATTRIBUTE_USED);
            }
            $this->repository->deleteAttribute($attributeId);
        });
    }

    public function createValue(int $attributeId, string $code, int $sortOrder, array $translations): int
    {
        $this->assertCanManage();
        $this->assertEnumAttribute($attributeId);
        $code = self::normalizeValueCode($code);
        self::assertSortOrder($sortOrder);
        $localized = $this->normalizeTranslations($translations, 'label');
        if (!$this->repository->isValueCodeAvailable($attributeId, $code)) {
            throw new InvalidArgumentException('Kód hodnoty již existuje.');
        }

        return $this->transaction->run(function () use ($attributeId, $code, $sortOrder, $localized): int {
            $id = $this->repository->createValue($attributeId, $code, $sortOrder);
            foreach ($localized as $locale => $label) {
                $this->repository->saveValueTranslation($id, $locale, $label);
            }
            return $id;
        });
    }

    public function updateValue(int $attributeId, int $valueId, string $code, int $sortOrder, array $translations): void
    {
        $this->assertCanManage();
        $this->assertEnumAttribute($attributeId);
        if ($valueId < 1 || !$this->repository->valueExists($attributeId, $valueId)) {
            throw new InvalidArgumentException('Hodnota atributu nebyla nalezena.');
        }
        $code = self::normalizeValueCode($code);
        self::assertSortOrder($sortOrder);
        $localized = $this->normalizeTranslations($translations, 'label');
        if (!$this->repository->isValueCodeAvailable($attributeId, $code, $valueId)) {
            throw new InvalidArgumentException('Kód hodnoty již existuje.');
        }

        $this->transaction->run(function () use ($attributeId, $valueId, $code, $sortOrder, $localized): void {
            $this->repository->updateValue($attributeId, $valueId, $code, $sortOrder);
            foreach ($localized as $locale => $label) {
                $this->repository->saveValueTranslation($valueId, $locale, $label);
            }
        });
    }

    public function deleteValue(int $attributeId, int $valueId): void
    {
        $this->assertCanManage();
        $this->assertEnumAttribute($attributeId);
        if ($valueId < 1 || !$this->repository->valueExists($attributeId, $valueId)) {
            throw new InvalidArgumentException('Hodnota atributu nebyla nalezena.');
        }
        $this->transaction->run(function () use ($attributeId, $valueId): void {
            if ($this->repository->isValueUsed($attributeId, $valueId)) {
                throw new AttributeInUseException(AttributeInUseException::VALUE_USED);
            }
            $this->repository->deleteValue($attributeId, $valueId);
        });
    }

    private function assertEnumAttribute(int $attributeId): void
    {
        if ($attributeId < 1 || $this->repository->findAttributeType($attributeId) !== AttributeType::Enum) {
            throw new InvalidArgumentException('Hodnoty lze spravovat pouze u atributu typu enum.');
        }
    }

    private function normalizeTranslations(array $translations, string $field): array
    {
        $normalized = [];
        foreach ($this->localeProvider->getActiveLocales() as $locale) {
            $code = $locale->getCode();
            $entry = $translations[$code] ?? null;
            $value = is_array($entry) ? ($entry[$field] ?? null) : $entry;
            if (!is_string($value)) {
                throw new InvalidArgumentException(sprintf('Chybí překlad pro aktivní jazyk %s.', $code));
            }
            $value = trim($value);
            if ($value === '' || mb_strlen($value) > 255 || preg_match('//u', $value) !== 1) {
                throw new InvalidArgumentException(sprintf('Překlad pro jazyk %s je neplatný.', $code));
            }
            $normalized[$code] = $value;
        }
        if (count($translations) !== count($normalized)) {
            throw new InvalidArgumentException('Překlady lze zadat pouze pro aktivní jazyky.');
        }
        return $normalized;
    }

    private function assertCanManage(): void
    {
        if (!$this->permission->canManage()) {
            throw new CatalogAccessDeniedException();
        }
    }

    private static function normalizeCode(string $code): string
    {
        $code = strtolower(trim($code));
        if (preg_match('/\A[a-z][a-z0-9_]{0,99}\z/D', $code) !== 1) {
            throw new InvalidArgumentException('Kód atributu musí obsahovat 1 až 100 malých ASCII písmen, číslic nebo podtržítek.');
        }
        return $code;
    }

    private static function normalizeValueCode(string $code): string
    {
        $code = strtolower(trim($code));
        if (preg_match('/\A[a-z0-9][a-z0-9_-]{0,99}\z/D', $code) !== 1) {
            throw new InvalidArgumentException('Kód hodnoty musí obsahovat 1 až 100 malých ASCII písmen, číslic, pomlček nebo podtržítek.');
        }
        return $code;
    }

    private static function assertTypeAndUnit(AttributeType $type, ?int $unitId): void
    {
        if ($unitId !== null && ($unitId < 1 || $type !== AttributeType::Number)) {
            throw new InvalidArgumentException('Měrnou jednotku lze přiřadit pouze číselnému atributu.');
        }
    }

    private static function assertSortOrder(int $sortOrder): void
    {
        if ($sortOrder < 0) {
            throw new InvalidArgumentException('Pořadí nesmí být záporné.');
        }
    }
}
