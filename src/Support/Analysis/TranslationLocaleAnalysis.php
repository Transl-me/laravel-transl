<?php

declare(strict_types=1);

namespace Transl\Support\Analysis;

use Illuminate\Contracts\Support\Arrayable;
use Transl\Support\Analysis\TranslationSetAnalysis;
use Transl\Support\Analysis\TranslationLocaleSetsAnalysisSummary;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class TranslationLocaleAnalysis implements Arrayable
{
    public function __construct(
        public readonly TranslationLocaleSetsAnalysisSummary $summary,
        /**
         * @var array<string, TranslationSetAnalysis>
         */
        public readonly array $translation_sets,
    ) {
    }

    /**
     * @param array<string, TranslationSetAnalysis> $translation_sets
     */
    public static function new(
        TranslationLocaleSetsAnalysisSummary $summary,
        array $translation_sets,
    ): static {
        return new static (
            summary: $summary,
            translation_sets: $translation_sets,
        );
    }

    /**
     * @param array<string, TranslationSetAnalysis> $sets
     */
    public static function fromAnalysedSets(array $sets): static
    {
        return static::new(
            summary: TranslationLocaleSetsAnalysisSummary::fromAnalysedSets($sets),
            translation_sets: $sets,
        );
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary->toArray(),
            'translation_sets' => collect($this->translation_sets)->toArray(),
        ];
    }
}
