<?php

declare(strict_types=1);

namespace Transl\Support;

use Illuminate\Support\Collection;
use Transl\Support\TranslationLine;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @extends Collection<array-key, TranslationLine>
 * @phpstan-consistent-constructor
 */
class TranslationLineCollection extends Collection
{
    /**
     * Create a new collection.
     *
     * @param  Arrayable<array-key, TranslationLine>|iterable<array-key, TranslationLine>|null  $items
     * @return void
     */
    public function __construct($items = [])
    {
        $this->items = $this->arrayableItemsToTranslationLines($this->getArrayableItems($items));
    }

    /**
     * Converts raw translation contents, after being
     * "dottified" (could be by using `Arr::dot`),
     * into TranslationLines.
     *
     * @param  iterable<array-key, array>  $lines
     */
    public static function fromRawTranslationLines(iterable $lines): static
    {
        $lines = collect($lines)->map(static fn (mixed $value, string $key): TranslationLine => (
            TranslationLine::make(
                key: $key,
                value: $value,
                meta: null,
            )
        ));

        return new static($lines);
    }

    /**
     * Converts back to raw translation lines. These are
     * translation contents that have been "dottified"
     * (could be by using `Arr::dot`).
     */
    public function toRawTranslationLines(): array
    {
        return $this->reduce(static function (array $acc, TranslationLine $line): array {
            $acc[$line->key] = $line->value;

            return $acc;
        }, []);
    }

    /**
     * Converts back to raw translation lines while trying as
     * best as posible to reconstruct back their original values.
     */
    public function toRawTranslationLinesWithPotentiallyOriginalValues(): array
    {
        return $this->reduce(static function (array $acc, TranslationLine $line): array {
            $acc[$line->key] = $line->potentialOriginalValue();

            return $acc;
        }, []);
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array<array-key, array>
     */
    public function toArray(): array
    {
        return array_map(static fn (TranslationLine $line): array => $line->toArray(), $this->items);
    }

    /**
     * Converts items into TranslationLines.
     *
     * @param  array<array-key, array|TranslationLine>  $items
     * @return array<array-key, TranslationLine>
     */
    protected function arrayableItemsToTranslationLines(array $items): array
    {
        return array_reduce($items, function (array $acc, array|TranslationLine $item): array {
            if (!($item instanceof TranslationLine)) {
                $item = $this->arrayableItemToTranslationLine($item);
            }

            $acc[] = $item;

            return $acc;
        }, []);
    }

    /**
     * Converts an item into a TranslationLine.
     */
    protected function arrayableItemToTranslationLine(array $item): TranslationLine
    {
        return TranslationLine::new(...$item);
    }
}
