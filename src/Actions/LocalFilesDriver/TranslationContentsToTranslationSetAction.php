<?php

declare(strict_types=1);

namespace Transl\Actions\LocalFilesDriver;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Stringable;
use Transl\Support\TranslationSet;
use Transl\Support\TranslationLineCollection;
use Transl\Actions\LocalFilesDriver\AbstractLocalFilesDriverAction;

class TranslationContentsToTranslationSetAction extends AbstractLocalFilesDriverAction
{
    /**
     * Execute the action.
     */
    public function execute(
        array $contents,
        string $locale,
        ?string $group,
        ?string $namespace,
        ?array $meta,
    ): TranslationSet {
        $lines = $this->fromRawTranslationFileContentsToRawTranslationLines($contents);

        return TranslationSet::new(
            locale: $locale,
            group: $group,
            namespace: $namespace,
            lines: TranslationLineCollection::fromRawTranslationLines($lines),
            meta: $meta,
        );
    }

    protected function fromRawTranslationFileContentsToRawTranslationLines(
        array $contents,
        ?string $parentKey = null,
    ): array {
        return collect(Arr::dot($contents))
            ->mapWithKeys(function (mixed $value, string $key) use ($parentKey): array {
                $value = $this->rawTranslationLineValue($value);

                if (is_array($value) && !empty($value)) {
                    return $this->fromRawTranslationFileContentsToRawTranslationLines($value, $key);
                }

                if ($parentKey) {
                    $key = "{$parentKey}.{$key}";
                }

                return [
                    $key => $value,
                ];
            })
            ->toArray();
    }

    protected function rawTranslationLineValue(mixed $value): mixed
    {
        if ($value instanceof Closure) {
            return $this->rawTranslationLineValue(rescue($value));
        }

        if ($this->valueIsArrayConvertable($value)) {
            /**
             * Making use of the collection's `getArrayableItems`
             * method here to convert array-like values to an array.
             *
             * Also using `toArray` to convert inner `Arrayable`
             * values into an array.
             */
            // @phpstan-ignore-next-line
            $value = collect($value)->toArray();
        }

        return $value;
    }

    protected function valueIsArrayConvertable(mixed $value): bool
    {
        if (is_array($value)) {
            return true;
        }

        if (!is_object($value)) {
            return false;
        }

        if (($value instanceof Stringable) && !($value instanceof Enumerable)) {
            return false;
        }

        return true;
    }
}
