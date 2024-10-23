<?php

declare(strict_types=1);

namespace Transl\Support\Analysis;

use Transl\Support\TranslationSet;
use Transl\Support\TranslationLine;
use Illuminate\Contracts\Support\Arrayable;
use Transl\Support\Analysis\TranslationLineAnalysis;
use Transl\Support\Analysis\TranslationSetLinesAnalysisSummary;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class TranslationSetAnalysis implements Arrayable
{
    public function __construct(
        public readonly TranslationSetLinesAnalysisSummary $summary,
        /**
         * @var array<string, TranslationLineAnalysis>
         */
        public readonly array $lines,
    ) {
    }

    public static function new(TranslationSetLinesAnalysisSummary $summary, array $lines): static
    {
        return new static($summary, $lines);
    }

    /**
     * @param array<string, TranslationLineAnalysis> $lines
     */
    public static function fromAnalysedLines(array $lines): static
    {
        return static::new(
            summary: TranslationSetLinesAnalysisSummary::fromAnalysedLines($lines),
            lines: $lines,
        );
    }

    // public static function fromTranslationSet(TranslationSet $set): static
    // {
    //     $lines = $set
    //         ->lines
    //         ->toBase()
    //         ->reduce(function (array $acc, TranslationLine $line): array {
    //             $acc[$line->key] = TranslationLineAnalysis::fromTranslationLine($line);

    //             return $acc;
    //         }, []);

    //     return static::fromAnalysedLines($lines);
    // }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary->toArray(),
            'lines' => collect($this->lines)->toArray(),
        ];
    }
}
