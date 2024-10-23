<?php

declare(strict_types=1);

namespace Transl\Support\Analysis;

use Illuminate\Contracts\Support\Arrayable;
use Transl\Support\Analysis\TranslationLocaleAnalysis;

/**
 * @implements Arrayable<string, int>
 * @phpstan-consistent-constructor
 */
class ProjectAnalysisSummary implements Arrayable
{
    public function __construct(
        public readonly int $unique_translation_key_count,
        public readonly int $unique_translation_set_count,
        public readonly int $translation_key_count,
        public readonly int $translation_set_count,
        public readonly int $translation_line_count,
        public readonly int $translation_line_word_count,
        public readonly int $translation_line_character_count,
    ) {
    }

    public static function new(
        int $unique_translation_key_count,
        int $unique_translation_set_count,
        int $translation_key_count,
        int $translation_set_count,
        int $translation_line_count,
        int $translation_line_word_count,
        int $translation_line_character_count,
    ): static {
        return new static(
            unique_translation_key_count: $unique_translation_key_count,
            unique_translation_set_count: $unique_translation_set_count,
            translation_key_count: $translation_key_count,
            translation_set_count: $translation_set_count,
            translation_line_count: $translation_line_count,
            translation_line_word_count: $translation_line_word_count,
            translation_line_character_count: $translation_line_character_count,
        );
    }

    /**
     * @param array<string, TranslationLocaleAnalysis> $locales
     */
    public static function partiallyFromAnalysedLocales(
        int $unique_translation_key_count,
        int $unique_translation_set_count,
        int $translation_key_count,
        array $locales,
    ): static {
        $summaries = array_column($locales, 'summary');

        return static::new(
            unique_translation_key_count: $unique_translation_key_count,
            unique_translation_set_count: $unique_translation_set_count,
            translation_key_count: $translation_key_count,
            translation_set_count: array_sum(array_column($summaries, 'translation_set_count')),
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
            'unique_translation_key_count' => $this->unique_translation_key_count,
            'unique_translation_set_count' => $this->unique_translation_set_count,
            'translation_key_count' => $this->translation_key_count,
            'translation_set_count' => $this->translation_set_count,
            'translation_line_count' => $this->translation_line_count,
            'translation_line_word_count' => $this->translation_line_word_count,
            'translation_line_character_count' => $this->translation_line_character_count,
        ];
    }
}
