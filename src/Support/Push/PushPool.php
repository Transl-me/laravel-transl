<?php

declare(strict_types=1);

namespace Transl\Support\Push;

use Iterator;
use Countable;
use Transl\Support\TranslationSet;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Iterator<int, PushChunk>
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class PushPool implements Iterator, Countable, Arrayable
{
    private int $size = 0;

    private int $total = 0;

    private int $lastChunkNumber = 0;

    /**
     * @var array<int, PushChunk>
     */
    private array $chunks = [];

    public function __construct(
        public readonly int $max_size,
        public readonly int $max_chunk_size,
    ) {
    }

    /**
     * Named constructor.
     */
    public static function new(int $maxSize, int $maxChunkSize): static
    {
        return new static($maxSize, $maxChunkSize);
    }

    /**
     * The current pool size.
     */
    public function size(): int
    {
        return $this->size;
    }

    /**
     * The total stored translation sets.
     */
    public function total(): int
    {
        return $this->total;
    }

    /**
     * The maximum translation sets the pool can hold.
     */
    public function maxTotal(): int
    {
        return $this->max_size * $this->max_chunk_size;
    }

    /**
     * Determine whether the maximum chunks size
     * has been reached or not.
     */
    public function isFull(): bool
    {
        return $this->total >= $this->maxTotal();
    }

    /**
     * Determine whether their are defined
     * chunks or not.
     */
    public function isEmpty(): bool
    {
        return $this->size <= 0;
    }

    /**
     * Add a translation set to a chunk.
     */
    public function add(TranslationSet $translationSet): static
    {
        /** @var PushChunk|null $lastChunk */
        $lastChunk = last($this->chunks) ?: null;

        if (!$lastChunk || ($this->max_chunk_size <= $lastChunk->size())) {
            $lastChunk = PushChunk::new($this->lastChunkNumber + 1);

            $this->lastChunkNumber = $lastChunk->number;

            $this->size++;
        }

        $lastChunk->add($translationSet);

        $this->chunks[$lastChunk->number] = $lastChunk;

        $this->total++;

        return $this;
    }

    /**
     * Retrieve the next chunk if any and remove it from the list.
     */
    public function drip(): ?PushChunk
    {
        $chunk = array_shift($this->chunks);

        if ($chunk) {
            $this->size--;

            $this->total = $this->total - $chunk->size();
        }

        return $chunk;
    }

    // /**
    //  * Consume and remove all chunks.
    //  *
    //  * @param callable(PushChunk $chunk): void $callback
    //  */
    // public function drain(callable $callback): void
    // {
    //     while ($chunk = $this->drip()) {
    //         $callback($chunk);
    //     }
    // }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'max_size' => $this->max_size,
            'max_chunk_size' => $this->max_chunk_size,
            'size' => $this->size(),
            'chunks' => array_map(fn (PushChunk $chunk): array => $chunk->toArray(), $this->chunks),
        ];
    }

    /* Iterator methods
    ------------------------------------------------*/

    /**
     * Rewind the Iterator to the first element.
     */
    public function rewind(): void
    {
        //
    }

    /**
     * Checks if current position is valid.
     */
    public function valid(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Return the current element.
     */
    public function current(): ?PushChunk
    {
        return $this->drip();
    }

    /**
     * Move forward to next element.
     */
    public function next(): void
    {
        //
    }

    /**
     * Return the key of the current element.
     */
    public function key(): mixed
    {
        return $this->size;
    }

    /* Countable methods
    ------------------------------------------------*/

    /**
     * The pool size.
     */
    public function count(): int
    {
        return $this->size;
    }
}
