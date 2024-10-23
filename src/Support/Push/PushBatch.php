<?php

declare(strict_types=1);

namespace Transl\Support\Push;

use Transl\Support\TranslationSet;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class PushBatch implements Arrayable
{
    protected static int $max_pool_size = 5;
    protected static int $max_chunk_size = 10;

    protected int $total_pushed = 0;

    public function __construct(
        public readonly int $id,
        public readonly PushPool $pool,
        protected readonly int $total_pushable,
    ) {
    }

    /**
     * Named constructor.
     */
    public static function new(int $totalPushable): static
    {
        return new static(
            time(),
            PushPool::new(static::maxPoolSize(), static::maxChunkSize()),
            $totalPushable,
        );
    }

    /**
     * Update the maximum chunks allowed in the pool.
     * This determines the amount of chunks to be
     * concurrently sent to Transl.
     */
    public static function resetDefaultMaxPoolAndChunkSizes(): void
    {
        static::$max_pool_size = 5;
        static::$max_chunk_size = 10;
    }

    /**
     * Update the maximum chunks allowed in the pool.
     * This determines the amount of chunks to be
     * concurrently sent to Transl.
     */
    public static function setMaxPoolSize(int $value): void
    {
        static::$max_pool_size = $value;
    }

    /**
     * Update the maximum translation sets allowed per chunk.
     */
    public static function setMaxChunkSize(int $value): void
    {
        static::$max_chunk_size = $value;
    }

    /**
     * The maximum chunks allowed in the pool.
     * This determines the amount of chunks to be
     * concurrently sent to Transl.
     */
    public static function maxPoolSize(): int
    {
        return static::$max_pool_size;
    }

    /**
     * The maximum translation sets allowed per chunk.
     */
    public static function maxChunkSize(): int
    {
        return static::$max_chunk_size;
    }

    public function totalPushable(): int
    {
        return $this->total_pushable;
    }

    public function totalPushed(): int
    {
        return $this->total_pushed;
    }

    // public function isPending(): bool
    // {
    //     return $this->total_pushed < $this->total_pushable;
    // }

    // public function isFinished(): bool
    // {
    //     return $this->total_pushed >= $this->total_pushable;
    // }

    /**
     * Fill the pool.
     */
    public function add(TranslationSet $translationSet): static
    {
        $this->pool->add($translationSet);

        return $this;
    }

    /**
     * Fill the pool and run a callback if full.
     *
     * @param callable(): void $callback
     */
    public function addUntilPoolFull(TranslationSet $translationSet, callable $callback): void
    {
        $this->add($translationSet);

        if (!$this->pool->isFull()) {
            return;
        }

        $this->drainPool($callback);
    }

    /**
     * Run a callback if the pool is not empty.
     *
     * @param callable(): void $callback
     */
    public function ensurePoolDrained(callable $callback): void
    {
        if ($this->pool->isEmpty()) {
            return;
        }

        $this->drainPool($callback);
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,

            'max_pool_size' => static::maxPoolSize(),
            'max_chunk_size' => static::maxChunkSize(),

            'total_pushable' => $this->totalPushable(),
            'total_pushed' => $this->totalPushed(),

            'pool' => $this->pool->toArray(),
        ];
    }

    /**
     * Expects the provided callback to drain the pool.
     *
     * @param callable(): void $callback
     */
    protected function drainPool(callable $callback): void
    {
        $total = $this->pool->total();

        $callback();

        $this->total_pushed = $this->total_pushed + $total;
    }
}
