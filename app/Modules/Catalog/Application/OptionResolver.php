<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Application\Dto\CustomerOptionSelectionDto;
use App\Modules\Catalog\Application\Dto\ResolvedCustomerOptionDto;
use App\Modules\Catalog\Domain\Option\CustomerOptionType;
use InvalidArgumentException;

/**
 * Ověří a normalizuje výběr zákaznických voleb aktivního katalogového produktu.
 *
 * Výpočet cenových rozdílů záměrně patří do modulu Pricing.
 */
final readonly class OptionResolver
{
    private const MAX_TEXT_LENGTH = 500;

    public function __construct(
        private OptionResolutionRepository $repository,
    ) {}

    /**
     * @param array<int, array<string, mixed>> $selections Seznam položek optionId + valueId nebo textValue.
     *
     * @throws InvalidArgumentException Při neplatném, duplicitním nebo neúplném výběru.
     */
    public function resolve(int $productId, array $selections): ?CustomerOptionSelectionDto
    {
        if ($productId < 1) {
            throw new InvalidArgumentException('ID produktu musí být kladné.');
        }

        if (!array_is_list($selections)) {
            throw new InvalidArgumentException('Výběr zákaznických voleb musí být seznam.');
        }

        $definitions = $this->repository->findActiveOptionDefinitions($productId);

        if ($definitions === null) {
            return null;
        }

        $definitionsById = [];
        foreach ($definitions as $definition) {
            $definitionsById[$definition->option->id] = $definition;
        }

        /** @var array<int, true> $seenOptionIds */
        $seenOptionIds = [];
        /** @var array<int, array{valueId: int|null, textValue: string|null}> $selectedById */
        $selectedById = [];

        foreach ($selections as $selection) {
            if (!isset($selection['optionId']) || !is_int($selection['optionId']) || $selection['optionId'] < 1) {
                throw new InvalidArgumentException('Každý výběr musí obsahovat kladné celočíselné optionId.');
            }

            $optionId = $selection['optionId'];
            if (isset($seenOptionIds[$optionId])) {
                throw new InvalidArgumentException(sprintf('Volba %d byla odeslána více než jednou.', $optionId));
            }
            $seenOptionIds[$optionId] = true;

            $definition = $definitionsById[$optionId] ?? null;
            if ($definition === null) {
                throw new InvalidArgumentException(sprintf('Volba %d nepatří k tomuto produktu.', $optionId));
            }

            $hasValueId = array_key_exists('valueId', $selection);
            $hasTextValue = array_key_exists('textValue', $selection);

            if (
                count($selection) !== 2
                || !$hasValueId && !$hasTextValue
                || $hasValueId && $hasTextValue
            ) {
                throw new InvalidArgumentException('Výběr musí obsahovat optionId a právě jednu hodnotu valueId nebo textValue.');
            }

            if ($definition->option->type === CustomerOptionType::Choice) {
                $valueId = $selection['valueId'] ?? null;

                if (!is_int($valueId) || $valueId < 1) {
                    throw new InvalidArgumentException(sprintf('Volba %d vyžaduje platné valueId.', $optionId));
                }

                if (!in_array($valueId, $definition->valueIds, true)) {
                    throw new InvalidArgumentException(sprintf('Hodnota %d není povolena pro volbu %d.', $valueId, $optionId));
                }

                $selectedById[$optionId] = ['valueId' => $valueId, 'textValue' => null];
                continue;
            }

            $textValue = $selection['textValue'] ?? null;
            if (!is_string($textValue)) {
                throw new InvalidArgumentException(sprintf('Textová volba %d vyžaduje textValue.', $optionId));
            }

            $textValue = trim($textValue);
            if ($textValue === '') {
                if ($definition->option->isRequired) {
                    throw new InvalidArgumentException(sprintf('Povinná textová volba %d nesmí být prázdná.', $optionId));
                }

                continue;
            }

            if (preg_match('/\\A.{1,' . self::MAX_TEXT_LENGTH . '}\\z/us', $textValue) !== 1) {
                throw new InvalidArgumentException(sprintf('Text volby %d musí mít nejvýše %d znaků.', $optionId, self::MAX_TEXT_LENGTH));
            }

            $selectedById[$optionId] = ['valueId' => null, 'textValue' => $textValue];
        }

        foreach ($definitions as $definition) {
            if ($definition->option->isRequired && !isset($selectedById[$definition->option->id])) {
                throw new InvalidArgumentException(sprintf('Povinná volba %d nebyla vybrána.', $definition->option->id));
            }
        }

        ksort($selectedById, SORT_NUMERIC);

        $resolved = [];
        foreach ($selectedById as $optionId => $selection) {
            $resolved[] = new ResolvedCustomerOptionDto(
                $optionId,
                $definitionsById[$optionId]->option->type,
                $selection['valueId'],
                $selection['textValue'],
            );
        }

        return new CustomerOptionSelectionDto($productId, $resolved);
    }
}
