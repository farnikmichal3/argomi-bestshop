<?php

declare(strict_types=1);

namespace App\UI\Admin\Products;

use App\Modules\Catalog\Api\CatalogPermission;
use App\Modules\Catalog\Api\CategoryTreeItem;
use App\Modules\Catalog\Api\CategoryTreeReader;
use App\Modules\Catalog\Api\Dto\ProductAdminListFilter;
use App\Modules\Catalog\Api\ProductAdminListReader;
use App\Modules\Catalog\Domain\Product\ProductStatus;
use App\Modules\Catalog\Domain\Product\ProductType;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Presentation\TranslatedPresenter;
use App\Shared\Domain\Locale\InvalidLocaleException;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;

final class ProductsPresenter extends TranslatedPresenter
{
    public function __construct(
        private readonly ProductAdminListReader $productAdminListReader,
        private readonly CategoryTreeReader $categoryTreeReader,
        private readonly CatalogPermission $catalogPermission,
        private readonly LocaleProvider $localeProvider,
        private readonly TranslatorFactory $translatorFactory,
    ) {
        parent::__construct();
    }

    protected function getTranslationDomain(): TranslationDomain
    {
        return TranslationDomain::Catalog;
    }

    public function renderDefault(
        ?string $categoryId = null,
        ?string $status = null,
        ?string $type = null,
        ?string $page = null,
    ): void {
        if (!$this->catalogPermission->canView()) {
            throw new ForbiddenRequestException();
        }

        $parsedCategoryId = $this->parseOptionalPositiveInteger($categoryId, 'categoryId');
        $parsedPage = $page === null || $page === ''
            ? 1
            : $this->parsePositiveInteger($page, 'page');

        $selectedStatus = $status === null || $status === ''
            ? null
            : ProductStatus::tryFrom($status);

        if ($status !== null && $status !== '' && $selectedStatus === null) {
            throw new BadRequestException('Neplatný filtr stavu produktu.');
        }

        $selectedType = $type === null || $type === ''
            ? null
            : ProductType::tryFrom($type);

        if ($type !== null && $type !== '' && $selectedType === null) {
            throw new BadRequestException('Neplatný filtr typu produktu.');
        }

        $locale = $this->resolveLocale();
        $listing = $this->productAdminListReader->findProducts(
            new ProductAdminListFilter(
                categoryId: $parsedCategoryId,
                status: $selectedStatus,
                type: $selectedType,
                page: $parsedPage,
            ),
            $locale,
        );

        $categoryOptions = [];
        $this->appendCategoryOptions(
            $this->categoryTreeReader->findTree($locale),
            0,
            $categoryOptions,
        );

        $translator = $this->translatorFactory->createTranslator(TranslationDomain::Catalog, $locale);
        $items = [];

        foreach ($listing->items as $item) {
            $items[] = [
                'product' => $item,
                'categoryNames' => implode(', ', $item->categoryNames),
                'typeLabel' => $translator->translate('products.admin.type.' . $item->type->value),
                'statusLabel' => $translator->translate('products.admin.status.' . $item->status->value),
            ];
        }

        $this->template->items = $items;
        $this->template->categoryOptions = $categoryOptions;
        $this->template->selectedCategoryId = $parsedCategoryId === null ? '' : (string) $parsedCategoryId;
        $this->template->selectedStatus = $selectedStatus?->value ?? '';
        $this->template->selectedType = $selectedType?->value ?? '';
        $this->template->page = $listing->page;
        $this->template->totalPages = $listing->totalPages;
        $this->template->totalItems = $listing->totalItems;
        $this->template->hasPreviousPage = $listing->page > 1;
        $this->template->hasNextPage = $listing->page < $listing->totalPages;
        $this->template->canManage = $this->catalogPermission->canManage();
        $this->template->statuses = ProductStatus::cases();
        $this->template->types = ProductType::cases();
    }

    /**
     * @param list<CategoryTreeItem> $nodes
     * @param array<int, string> $options
     */
    private function appendCategoryOptions(array $nodes, int $depth, array &$options): void
    {
        foreach ($nodes as $node) {
            $options[$node->id] = str_repeat('— ', $depth) . ($node->name ?? $node->code);
            $this->appendCategoryOptions($node->children, $depth + 1, $options);
        }
    }

    private function parseOptionalPositiveInteger(?string $value, string $parameter): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->parsePositiveInteger($value, $parameter);
    }

    private function parsePositiveInteger(string $value, string $parameter): int
    {
        $parsed = filter_var($value, FILTER_VALIDATE_INT);

        if (!is_int($parsed) || $parsed < 1) {
            throw new BadRequestException(sprintf('Neplatná hodnota parametru %s.', $parameter));
        }

        return $parsed;
    }

    private function resolveLocale(): Locale
    {
        $language = $this->getParameter('lang');

        if ($language === null) {
            return $this->localeProvider->getDefault();
        }

        if (!is_string($language)) {
            throw new BadRequestException('Neplatný jazyk požadavku.');
        }

        try {
            $locale = Locale::fromString($language);
        } catch (InvalidLocaleException $exception) {
            throw new BadRequestException('Neplatný jazyk požadavku.', previous: $exception);
        }

        if (!$this->localeProvider->isActive($locale)) {
            throw new BadRequestException('Požadovaný jazyk není aktivní.');
        }

        return $locale;
    }
}
