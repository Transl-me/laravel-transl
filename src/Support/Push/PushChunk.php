<?php

declare(strict_types=1);

namespace Transl\Support\Push;

use Transl\Support\TranslationSet;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class PushChunk implements Arrayable
{
    private int $size = 0;

    /**
     * @var TranslationSet[]
     */
    private array $translation_sets = [];

    public function __construct(
        public readonly int $number,
    ) {
    }

    /**
     * Named constructor.
     */
    public static function new(int $number): static
    {
        return new static($number);
    }

    /**
     * Add a translation set to the chunk.
     */
    public function add(TranslationSet $translationSet): static
    {
        $this->translation_sets[] = $translationSet;

        $this->size++;

        return $this;
    }

    /**
     * The current chunk size.
     */
    public function size(): int
    {
        return $this->size;
    }

    /**
     * The translation sets of the chunk.
     *
     * @return TranslationSet[]
     */
    public function translationSets(): array
    {
        return $this->translation_sets;
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'size' => $this->size(),
            'translation_sets' => array_map(
                fn (TranslationSet $translationSet): array => $translationSet->toArray(),
                $this->translationSets(),
            ),
        ];
    }
}
