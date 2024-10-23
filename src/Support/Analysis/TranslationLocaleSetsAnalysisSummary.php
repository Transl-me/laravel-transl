<?php

declare(strict_types=1);

namespace Transl\Support\Analysis;

use Illuminate\Contracts\Support\Arrayable;
use Transl\Support\Analysis\TranslationSetAnalysis;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class TranslationLocaleSetsAnalysisSummary implements Arrayable
{
    public function __construct(
        public readonly int $translation_set_count,
        public readonly int $translation_line_count,
        public readonly int $translation_line_word_count,
        public readonly int $translation_line_character_count,
    ) {
    }

    public static function new(
        int $translation_set_count,
        int $translation_line_count,
        int $translation_line_word_count,
        int $translation_line_character_count,
    ): static {
        return new static(
            translation_set_count: $translation_set_count,
            translation_line_count: $translation_line_count,
            translation_line_word_count: $translation_line_word_count,
            translation_line_character_count: $translation_line_character_count,
        );
    }

    /**
     * @param array<string, TranslationSetAnalysis> $sets
     */
    public static function fromAnalysedSets(array $sets): static
    {
        $summaries = array_column($sets, 'summary');

        return static::new(
            translation_set_count: count($sets),
            translation_line_count: array_sum(array_column($summaries, 'translation_line_count')),
            translation_line_word_count: array_sum(array_column($summaries, 'translation_line_word_count')),
            translation_line_character_count: array_sum(array_column($summaries, 'translation_line_character_count')),
        );
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'translation_set_count' => $this->translation_set_count,
            'translation_line_count' => $this->translation_line_count,
            'translation_line_word_count' => $this->translation_line_word_count,
            'translation_line_character_count' => $this->translation_line_character_count,
        ];
    }
}
