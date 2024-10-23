<?php

declare(strict_types=1);

namespace Transl\Support\Analysis;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class TranslationSetLinesAnalysisSummary implements Arrayable
{
    public function __construct(
        public readonly int $translation_line_count,
        public readonly int $translation_line_word_count,
        public readonly int $translation_line_character_count,
    ) {
    }

    public static function new(
        int $translation_line_count,
        int $translation_line_word_count,
        int $translation_line_character_count,
    ): static {
        return new static($translation_line_count, $translation_line_word_count, $translation_line_character_count);
    }

    /**
     * @param array<string, TranslationLineAnalysis> $lines
     */
    public static function fromAnalysedLines(array $lines): static
    {
        return static::new(
            translation_line_count: count($lines),
            translation_line_word_count: array_sum(array_column($lines, 'word_count')),
            translation_line_character_count: array_sum(array_column($lines, 'character_count')),
        );
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'translation_line_count' => $this->translation_line_count,
            'translation_line_word_count' => $this->translation_line_word_count,
            'translation_line_character_count' => $this->translation_line_character_count,
        ];
    }
}
