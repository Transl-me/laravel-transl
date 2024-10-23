<?php

declare(strict_types=1);

namespace Transl\Support;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Support\Arrayable;
use Transl\Support\TranslationLineCollection;

/**
 * @implements Arrayable<string, array>
 * @phpstan-consistent-constructor
 */
class TranslationLinesDiffing implements Arrayable
{
    protected array $CACHE = [];

    public function __construct(
        protected readonly TranslationLineCollection $trackedLines,
        protected readonly TranslationLineCollection $currentLines,
        protected readonly TranslationLineCollection $incomingLines,
    ) {
    }

    /**
     * @param TranslationLineCollection $trackedLines The saved tracked lines.
     * @param TranslationLineCollection $currentLines The currently untracked lines.
     * @param TranslationLineCollection $incomingLines The incoming, remotely tracked lines.
     */
    public static function new(
        TranslationLineCollection $trackedLines,
        TranslationLineCollection $currentLines,
        TranslationLineCollection $incomingLines,
    ): static {
        return new static($trackedLines, $currentLines, $incomingLines);
    }

    /**
     * The saved tracked lines.
     */
    public function trackedLines(): TranslationLineCollection
    {
        return $this->trackedLines;
    }

    /**
     * The currently untracked lines.
     */
    public function currentLines(): TranslationLineCollection
    {
        return $this->currentLines;
    }

    /**
     * The incoming, remotely tracked lines.
     */
    public function incomingLines(): TranslationLineCollection
    {
        return $this->incomingLines;
    }

    /**
     * Returns lines in `incoming` that differs from `current`.
     */
    public function changedLines(): TranslationLineCollection
    {
        return $this->cache(__METHOD__, function (): TranslationLineCollection {
            return $this->linesInTargetThatDiffersFromSource($this->incomingLines(), $this->currentLines());
        });
    }

    /**
     * Returns lines in `changed` that are missing in `added`.
     */
    public function updatedLines(): TranslationLineCollection
    {
        return $this->cache(__METHOD__, function (): TranslationLineCollection {
            return $this->linesInSourceThatAreMissingInTarget($this->changedLines(), $this->addedLines());
        });
    }

    /**
     * Returns lines in `incoming` that are the same in `current`.
     */
    public function sameLines(): TranslationLineCollection
    {
        return $this->cache(__METHOD__, function (): TranslationLineCollection {
            return $this->linesInSourceThatAreTheSameInTarget($this->incomingLines(), $this->currentLines());
        });
    }

    /**
     * Returns lines in `incoming` that are missing in `current`.
     */
    public function addedLines(): TranslationLineCollection
    {
        return $this->cache(__METHOD__, function (): TranslationLineCollection {
            return $this->linesInSourceThatAreMissingInTarget($this->incomingLines(), $this->currentLines());
        });
    }

    /**
     * Returns lines in `tracked` that are missing in `incoming`.
     */
    public function removedLines(): TranslationLineCollection
    {
        return $this->cache(__METHOD__, function (): TranslationLineCollection {
            return $this->linesInSourceThatAreMissingInTarget($this->trackedLines(), $this->incomingLines());
        });
    }

    /**
     * Returns lines between `tracked`, `current` & `incoming` that
     * are no longer compatible and cannot be programmatically mergered;
     * requiring the developer's input.
     */
    public function conflictingLines(): TranslationLineCollection
    {
        /**
         * Heuristics (for same line changes):
         * - current:same    | incoming:same    ---> ok
         * - current:same    | incoming:added   ---> ok
         * - current:same    | incoming:updated ---> ok
         * - current:same    | incoming:removed ---> ok
         *
         * - current:added   | incoming:same    ---> ok
         * - current:added   | incoming:added   ---> ok
         * - current:added   | incoming:updated ---> ok
         * - current:added   | incoming:removed ---> ok
         *
         * - current:updated | incoming:same    ---> conflict
         * - current:updated | incoming:added   ---> conflict
         * - current:updated | incoming:updated ---> conflict (if different changes)
         * - current:updated | incoming:removed ---> conflict
         *
         * - current:removed | incoming:same    ---> conflict
         * - current:removed | incoming:added   ---> conflict
         * - current:removed | incoming:updated ---> conflict
         * - current:removed | incoming:removed ---> ok
         */
        return $this->cache(__METHOD__, function (): TranslationLineCollection {
            // $currentAdded = $this->linesInSourceThatAreMissingInTarget($this->currentLines(), $this->trackedLines());
            $currentChanged = $this->linesInTargetThatDiffersFromSource($this->currentLines(), $this->trackedLines());

            $current = [
                // 'same' => $this->linesInSourceThatAreTheSameInTarget($this->currentLines(), $this->trackedLines()),
                // 'added' => $currentAdded,
                'changed' => $currentChanged,
                // 'updated' => $this->linesInSourceThatAreMissingInTarget($currentChanged, $currentAdded),
                'removed' => $this->linesInSourceThatAreMissingInTarget($this->trackedLines(), $this->currentLines()),
            ];
            $incoming = [
                // 'same' => $this->sameLines(),
                // 'added' => $this->addedLines(),
                'changed' => $this->changedLines(),
                // 'updated' => $this->updatedLines(),
                'removed' => $this->removedLines(),
            ];

            $conflicting = TranslationLineCollection::make();

            if ($current['changed']->isNotEmpty()) {
                $conflicting->push(...$this->linesInSourceThatExistsInTarget($incoming['changed'], $current['changed']));
                $conflicting->push(...$this->linesInSourceThatExistsInTarget($incoming['removed'], $current['changed']));
            }

            if ($current['removed']->isNotEmpty()) {
                $conflicting->push(...$this->linesInSourceThatAreMissingInTarget($current['removed'], $incoming['removed']));
            }

            return $conflicting;
        });
    }

    /**
     * Returns lines in `changed` that are missing in `conflicting`.
     */
    public function nonConflictingLines(): TranslationLineCollection
    {
        return $this->cache(__METHOD__, function (): TranslationLineCollection {
            return $this->linesInSourceThatAreMissingInTarget(
                $this->changedLines(),
                $this->conflictingLines(),
            );
        });
    }

    /**
     * Returns lines from `same` merged with lines from `nonConflicting`.
     */
    public function safeLines(): TranslationLineCollection
    {
        return $this->cache(__METHOD__, function (): TranslationLineCollection {
            return TranslationLineCollection::fromRawTranslationLines([
                ...$this->sameLines()->toRawTranslationLinesWithPotentiallyOriginalValues(),
                ...$this->nonConflictingLines()->toRawTranslationLinesWithPotentiallyOriginalValues(),
            ]);
        });
    }

    /**
     * Returns lines from `current` merged with lines from `safe`
     * minus non conflicting removed lines.
     */
    public function mergeableLines(): TranslationLineCollection
    {
        return $this->cache(__METHOD__, function (): TranslationLineCollection {
            $lines = collect([
                ...$this->currentLines()->toRawTranslationLinesWithPotentiallyOriginalValues(),
                ...$this->safeLines()->toRawTranslationLinesWithPotentiallyOriginalValues(),
            ]);

            $conflictingKeys = array_keys($this->conflictingLines()->toRawTranslationLinesWithPotentiallyOriginalValues());

            $nonConflictingRemoved = Arr::except($this->removedLines()->toRawTranslationLinesWithPotentiallyOriginalValues(), $conflictingKeys);

            $lines->forget(array_keys($nonConflictingRemoved));

            return TranslationLineCollection::fromRawTranslationLines($lines);
        });
    }

    // /**
    //  * Returns merged lines between tracked & current & incoming
    //  * with the tracked lines taking precedence over fall.
    //  */
    // public function favorTrackedLines(): TranslationLineCollection
    // {
    //     return $this->cache(__METHOD__, function (): TranslationLineCollection {
    //         return TranslationLineCollection::fromRawTranslationLines([
    //             ...$this->trackedLines()->toRawTranslationLinesWithPotentiallyOriginalValues(),
    //             // Lines added in current
    //             ...$this->linesInSourceThatAreMissingInTarget(
    //                 $this->currentLines(),
    //                 $this->trackedLines(),
    //             )->toRawTranslationLinesWithPotentiallyOriginalValues(),
    //             ...$this->addedLines()->toRawTranslationLinesWithPotentiallyOriginalValues(),
    //         ]);
    //     });
    // }

    /**
     * Returns merged lines between current & incoming with
     * the current lines taking precedence.
     */
    public function favorCurrentLines(): TranslationLineCollection
    {
        return $this->cache(__METHOD__, function (): TranslationLineCollection {
            $lines = collect([
                ...$this->currentLines()->toRawTranslationLinesWithPotentiallyOriginalValues(),
                ...$this->addedLines()->toRawTranslationLinesWithPotentiallyOriginalValues(),
            ]);

            $currentRemoved = $this->linesInSourceThatAreMissingInTarget($this->trackedLines(), $this->currentLines());

            $lines->forget(array_keys($currentRemoved->toRawTranslationLinesWithPotentiallyOriginalValues()));

            return TranslationLineCollection::fromRawTranslationLines($lines);
        });
    }

    /**
     * Returns merged lines between current & incoming with
     * the incoming lines taking precedence.
     */
    public function favorIncomingLines(): TranslationLineCollection
    {
        return $this->cache(__METHOD__, function (): TranslationLineCollection {
            $lines = collect([
                ...$this->currentLines()->toRawTranslationLinesWithPotentiallyOriginalValues(),
                ...$this->updatedLines()->toRawTranslationLinesWithPotentiallyOriginalValues(),
                ...$this->addedLines()->toRawTranslationLinesWithPotentiallyOriginalValues(),
            ]);

            $lines->forget(array_keys($this->removedLines()->toRawTranslationLinesWithPotentiallyOriginalValues()));

            return TranslationLineCollection::fromRawTranslationLines($lines);
        });
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'tracked_lines' => $this->trackedLines()->toArray(),
            'current_lines' => $this->currentLines()->toArray(),
            'incoming_lines' => $this->incomingLines()->toArray(),
        ];
    }

    // protected function linesInSourceThatChangedInTarget(
    //     TranslationLineCollection $source,
    //     TranslationLineCollection $target,
    // ): TranslationLineCollection {
    //     $source = collect($source->toRawTranslationLinesWithPotentiallyOriginalValues());
    //     $target = collect($target->toRawTranslationLinesWithPotentiallyOriginalValues());

    //     return TranslationLineCollection::fromRawTranslationLines($source->diffAssoc($target));
    // }

    protected function linesInTargetThatDiffersFromSource(
        TranslationLineCollection $target,
        TranslationLineCollection $source,
    ): TranslationLineCollection {
        $target = collect($target->toRawTranslationLinesWithPotentiallyOriginalValues());
        $source = collect($source->toRawTranslationLinesWithPotentiallyOriginalValues());

        /**
         * Does not handle empty array values.
         * Throws "Array to string conversion".
         */
        // return TranslationLineCollection::fromRawTranslationLines($target->diffAssoc($source));

        $lines = $target->filter(static function (mixed $value, string $key) use ($source): bool {
            return $value !== $source->get($key);
        });

        return TranslationLineCollection::fromRawTranslationLines($lines);
    }

    protected function linesInSourceThatAreTheSameInTarget(
        TranslationLineCollection $source,
        TranslationLineCollection $target,
    ): TranslationLineCollection {
        $source = collect($source->toRawTranslationLinesWithPotentiallyOriginalValues());
        $target = collect($target->toRawTranslationLinesWithPotentiallyOriginalValues());

        /**
         * Does not handle empty array values.
         * Throws "Array to string conversion".
         */
        // return TranslationLineCollection::fromRawTranslationLines($source->intersectAssoc($target));

        $lines = $source->filter(static function (mixed $value, string $key) use ($target): bool {
            return $value === $target->get($key);
        });

        return TranslationLineCollection::fromRawTranslationLines($lines);
    }

    protected function linesInSourceThatAreMissingInTarget(
        TranslationLineCollection $source,
        TranslationLineCollection $target,
    ): TranslationLineCollection {
        $source = collect($source->toRawTranslationLinesWithPotentiallyOriginalValues());
        $target = collect($target->toRawTranslationLinesWithPotentiallyOriginalValues());

        return TranslationLineCollection::fromRawTranslationLines($source->diffKeys($target));
    }

    protected function linesInSourceThatExistsInTarget(
        TranslationLineCollection $source,
        TranslationLineCollection $target,
    ): TranslationLineCollection {
        $source = collect($source->toRawTranslationLinesWithPotentiallyOriginalValues());
        $target = collect($target->toRawTranslationLinesWithPotentiallyOriginalValues());

        return TranslationLineCollection::fromRawTranslationLines($source->intersectByKeys($target));
    }

    protected function cache(string $key, Closure $callback): TranslationLineCollection
    {
        if (!isset($this->CACHE[$key])) {
            $this->CACHE[$key] = $callback();
        }

        return $this->CACHE[$key];
    }
}
