<?php

declare(strict_types=1);

namespace Transl\Support\Contracts;

use Transl\Support\Branch;
use Transl\Support\TranslationSet;
use Transl\Config\ProjectConfiguration;

interface Driverable
{
    /* Translation set
    ------------------------------------------------*/

    /**
     * Count the amount of translation sets to be retrieved.
     *
     * @param (callable(string $locale, ?string $group, ?string $namespace): bool)|null $filter
     * @param (callable(TranslationSet $translationSet): void)|null $onSkipped
     */
    public function countTranslationSets(
        ProjectConfiguration $project,
        Branch $branch,
        ?callable $filter = null,
        ?callable $onSkipped = null,
    ): int;

    /**
     * Get a collection of `\Transl\Support\TranslationSet`s.
     *
     * @param (callable(string $locale, ?string $group, ?string $namespace): bool)|null $filter
     * @param (callable(TranslationSet $translationSet): void)|null $onSkipped
     * @return iterable<TranslationSet>
     */
    public function getTranslationSets(
        ProjectConfiguration $project,
        Branch $branch,
        ?callable $filter = null,
        ?callable $onSkipped = null,
    ): iterable;

    /**
     * Retrieves a `\Transl\Support\TranslationSet`.
     */
    public function getTranslationSet(
        ProjectConfiguration $project,
        Branch $branch,
        string $locale,
        ?string $group,
        ?string $namespace,
        ?array $meta,
    ): TranslationSet;

    /**
     * Stores a `\Transl\Support\TranslationSet` in a way
     * that should be readable to a Laravel translation loader.
     */
    public function saveTranslationSet(
        ProjectConfiguration $project,
        Branch $branch,
        TranslationSet $set,
    ): void;

    /* Tracked translation set
    ------------------------------------------------*/

    /**
     * Get a `\Transl\Support\TranslationSet` that has
     * previously been pushed to Transl, thus, "tracked" by Transl.
     * This translation set will be used in determining conflicts.
     */
    public function getTrackedTranslationSet(
        ProjectConfiguration $project,
        Branch $branch,
        TranslationSet $set,
    ): ?TranslationSet;

    /**
     * Stores a `\Transl\Support\TranslationSet` in a way
     * that would allow it to be reconstructed back (using `TranslationSet::from` for example).
     */
    public function saveTrackedTranslationSet(
        ProjectConfiguration $project,
        Branch $branch,
        TranslationSet $set,
    ): void;
}
