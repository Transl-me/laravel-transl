<?php

declare(strict_types=1);

namespace Transl\Support\Analysis;

use Transl\Support\TranslationSet;
use Transl\Support\TranslationLine;
use Illuminate\Contracts\Support\Arrayable;
use Transl\Support\Analysis\ProjectAnalysisSummary;
use Transl\Support\Analysis\TranslationSetAnalysis;
use Transl\Support\Analysis\TranslationLineAnalysis;
use Transl\Support\Analysis\TranslationLocaleAnalysis;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class ProjectAnalysis implements Arrayable
{
    public function __construct(
        public readonly ProjectAnalysisSummary $summary,
        /**
         * @var array<string, TranslationLocaleAnalysis>
         */
        public readonly array $locales,
    ) {
    }

    /**
     * @param array<string, TranslationLocaleAnalysis> $locales
     */
    public static function new(ProjectAnalysisSummary $summary, array $locales): static
    {
        return new static($summary, $locales);
    }

    /**
     * @param iterable<TranslationSet> $sets
     */
    public static function fromTranslationSets(iterable $sets): static
    {
        $uniqueTranslationKeys = [];
        $uniqueTranslationSets = [];
        $locales = [];
        $analysedLocales = [];

        foreach ($sets as $set) {
            $translationSetKey = $set->translationKey();
            $translationSetLabel = $translationSetKey === '' ? 'UNGROUPED' : $translationSetKey;

            $uniqueTranslationSets[$translationSetKey] = 1;

            $lines = $set
                ->lines
                ->toBase()
                ->reduce(static function (array $acc, TranslationLine $line) use ($translationSetKey, &$uniqueTranslationKeys): array {
                    $translationKey = $translationSetKey === '' ? $line->key : "{$translationSetKey}.{$line->key}";

                    $uniqueTranslationKeys[$translationKey] = ($uniqueTranslationKeys[$translationKey] ?? 0) + 1;

                    $acc[$line->key] = TranslationLineAnalysis::fromTranslationLine($line);

                    return $acc;
                }, []);

            ksort($lines);

            $locales[$set->locale][$translationSetLabel] = TranslationSetAnalysis::fromAnalysedLines($lines);
        }

        foreach ($locales as $locale => $analysedSets) {
            ksort($analysedSets);

            $analysedLocales[$locale] = TranslationLocaleAnalysis::fromAnalysedSets($analysedSets);
        }

        ksort($analysedLocales);

        return static::new(
            summary: ProjectAnalysisSummary::partiallyFromAnalysedLocales(
                unique_translation_key_count: count($uniqueTranslationKeys),
                unique_translation_set_count: count($uniqueTranslationSets),
                translation_key_count: array_sum($uniqueTranslationKeys),
                locales: $analysedLocales,
            ),
            locales: $analysedLocales,
        );
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary->toArray(),
            'locales' => collect($this->locales)->toArray(),
        ];
    }
}
