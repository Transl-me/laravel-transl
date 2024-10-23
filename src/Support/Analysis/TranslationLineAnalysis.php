<?php

declare(strict_types=1);

namespace Transl\Support\Analysis;

use Illuminate\Support\Str;
use Transl\Support\TranslationLine;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, int>
 * @phpstan-consistent-constructor
 */
class TranslationLineAnalysis implements Arrayable
{
    public function __construct(
        public readonly int $word_count,
        public readonly int $character_count,
    ) {
    }

    public static function new(int $word_count, int $character_count): static
    {
        return new static($word_count, $character_count);
    }

    public static function fromString(string $value): static
    {
        return static::new(
            word_count: static::valueWordCount($value),
            character_count: static::valueCharacterCount($value),
        );
    }

    public static function fromTranslationLine(TranslationLine $line): static
    {
        return static::fromString($line->valueAsString());
    }

    protected static function valueWordCount(string $value): int
    {
        // return Str::wordCount($value);
        return collect(explode(' ', $value))
            ->map(static fn (string $value): string => trim($value))
            ->filter()
            ->values()
            ->count();
    }

    protected static function valueCharacterCount(string $value): int
    {
        return Str::length($value);
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'word_count' => $this->word_count,
            'character_count' => $this->character_count,
        ];
    }
}
