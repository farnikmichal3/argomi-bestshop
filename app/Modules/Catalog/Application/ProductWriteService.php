<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Api\CatalogAccessDeniedException;
use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\ProductAdminStatus;
use App\Modules\Catalog\Api\ProductAdminType;
use App\Modules\Catalog\Api\ProductWriter;
use App\Modules\Catalog\Domain\Product\ProductStatus;
use App\Modules\Catalog\Domain\Product\ProductType;
use App\Modules\Catalog\Domain\Slug\SlugTarget;
use App\Shared\Application\Transaction\Transaction;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Locale\LocaleProvider;
use InvalidArgumentException;

final class ProductWriteService implements ProductWriter
{
    public function __construct(
        private readonly ProductWriteRepository $repository,
        private readonly LocaleProvider $localeProvider,
        private readonly SlugService $slugService,
        private readonly Transaction $transaction,
        private readonly ProductDescriptionSanitizer $descriptionSanitizer,
        private readonly CatalogPermission $permission,
    ) {}

    /**
     * @param array<string, mixed> $translations
     * @param list<int> $categoryIds
     */
    public function create(
        string $code,
        ProductAdminStatus $status,
        ProductAdminType $type,
        int $taxClassId,
        array $translations,
        array $categoryIds,
    ): int {
        $this->assertCanManage();
        $domainStatus = ProductStatus::from($status->value);
        $domainType = ProductType::from($type->value);
        $code = self::normalizeCode($code);
        $localized = $this->normalizeTranslations($translations);
        $categories = self::normalizeCategoryIds($categoryIds);
        $this->assertReferences($taxClassId, $categories);

        if (!$this->repository->isCodeAvailable($code)) {
            throw new InvalidArgumentException('Kód produktu již existuje.');
        }

        return $this->transaction->run(function () use ($code, $domainStatus, $domainType, $taxClassId, $localized, $categories): int {
            $productId = $this->repository->createProduct($code, $domainStatus, $domainType, $taxClassId);
            $this->saveTranslations($productId, $localized);
            $this->repository->replaceCategories($productId, $categories);
            return $productId;
        });
    }

    /**
     * @param array<string, mixed> $translations
     * @param list<int> $categoryIds
     */
    public function update(
        int $productId,
        string $code,
        ProductAdminStatus $status,
        ProductAdminType $type,
        int $taxClassId,
        array $translations,
        array $categoryIds,
    ): void {
        $this->assertCanManage();
        $domainStatus = ProductStatus::from($status->value);
        $domainType = ProductType::from($type->value);
        if ($productId < 1 || !$this->repository->productExists($productId)) {
            throw new InvalidArgumentException('Produkt nebyl nalezen.');
        }

        $code = self::normalizeCode($code);
        $localized = $this->normalizeTranslations($translations);
        $categories = self::normalizeCategoryIds($categoryIds);
        $this->assertReferences($taxClassId, $categories);

        if (!$this->repository->isCodeAvailable($code, $productId)) {
            throw new InvalidArgumentException('Kód produktu již existuje.');
        }

        $this->transaction->run(function () use ($productId, $code, $domainStatus, $domainType, $taxClassId, $localized, $categories): void {
            $this->repository->updateProduct($productId, $code, $domainStatus, $domainType, $taxClassId);
            $this->saveTranslations($productId, $localized);
            $this->repository->replaceCategories($productId, $categories);
        });
    }

    /**
     * @param array<string, mixed> $translations
     * @return array<string, array{name: string, shortDescription: ?string, description: ?string, metaTitle: ?string, metaDescription: ?string}>
     */
    private function normalizeTranslations(array $translations): array
    {
        $normalized = [];
        foreach ($this->localeProvider->getActiveLocales() as $locale) {
            $code = $locale->getCode();
            $entry = $translations[$code] ?? null;
            if (!is_array($entry) || !isset($entry['name']) || !is_string($entry['name'])) {
                throw new InvalidArgumentException(sprintf('Chybí název produktu pro aktivní jazyk %s.', $code));
            }

            $name = trim($entry['name']);
            if ($name === '' || mb_strlen($name) > 255 || preg_match('//u', $name) !== 1) {
                throw new InvalidArgumentException(sprintf('Název produktu pro %s je neplatný.', $code));
            }

            $normalized[$code] = [
                'name' => $name,
                'shortDescription' => self::optionalText($entry['shortDescription'] ?? null, 2000, $code),
                'description' => $this->descriptionSanitizer->sanitize(self::optionalText($entry['description'] ?? null, null, $code)),
                'metaTitle' => self::optionalText($entry['metaTitle'] ?? null, 255, $code),
                'metaDescription' => self::optionalText($entry['metaDescription'] ?? null, 500, $code),
            ];
        }

        if (count($translations) !== count($normalized)) {
            throw new InvalidArgumentException('Překlady mohou být zadány pouze pro aktivní jazyky.');
        }

        return $normalized;
    }

    /**
     * @param array<string, array{name: string, shortDescription: ?string, description: ?string, metaTitle: ?string, metaDescription: ?string}> $translations
     */
    private function saveTranslations(int $productId, array $translations): void
    {
        foreach ($translations as $code => $entry) {
            $locale = Locale::fromString($code);
            $slug = $this->slugService->generate(SlugTarget::Product, $entry['name'], $locale, $productId);
            $this->repository->saveTranslation(
                $productId,
                $locale,
                $entry['name'],
                $slug,
                $entry['shortDescription'],
                $entry['description'],
                $entry['metaTitle'],
                $entry['metaDescription'],
            );
        }
    }

    /** @param list<int> $categoryIds */
    private function assertReferences(int $taxClassId, array $categoryIds): void
    {
        if ($taxClassId < 1 || !$this->repository->taxClassExists($taxClassId)) {
            throw new InvalidArgumentException('Vybraná daňová třída neexistuje.');
        }

        foreach ($categoryIds as $categoryId) {
            if (!$this->repository->categoryExists($categoryId)) {
                throw new InvalidArgumentException('Jedna z vybraných kategorií neexistuje.');
            }
        }
    }

    /** @param array<mixed> $categoryIds
     *  @return list<int>
     */
    private static function normalizeCategoryIds(array $categoryIds): array
    {
        $normalized = [];
        foreach ($categoryIds as $categoryId) {
            if (!is_int($categoryId) || $categoryId < 1 || in_array($categoryId, $normalized, true)) {
                throw new InvalidArgumentException('Přiřazení kategorií obsahuje neplatné nebo duplicitní ID.');
            }
            $normalized[] = $categoryId;
        }
        return $normalized;
    }

    private static function optionalText(mixed $value, ?int $maxLength, string $locale): ?string
    {
        if ($value === null) {
            return null;
        }
        if (!is_string($value) || preg_match('//u', $value) !== 1) {
            throw new InvalidArgumentException(sprintf('Text produktu pro %s není platný UTF-8 řetězec.', $locale));
        }
        $value = trim($value);
        if ($maxLength !== null && mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException(sprintf('Text produktu pro %s překračuje povolenou délku.', $locale));
        }
        return $value === '' ? null : $value;
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
        if (preg_match('/\A[a-z0-9][a-z0-9_-]{0,99}\z/D', $code) !== 1) {
            throw new InvalidArgumentException('Kód produktu musí obsahovat 1 až 100 malých ASCII písmen, číslic, pomlček nebo podtržítek.');
        }
        return $code;
    }
}
