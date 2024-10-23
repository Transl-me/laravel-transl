<?php

declare(strict_types=1);

namespace Transl\Support;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class Branch implements Arrayable
{
    public function __construct(
        /**
         * The branch name.
         */
        public readonly string $name,
        /**
         * If the branch has been provided by the user.
         */
        public readonly bool $is_provided,
        /**
         * If the branch is the determined current working branch.
         */
        public readonly bool $is_current,
        /**
         * If the branch is the determined default.
         */
        public readonly bool $is_default,
        /**
         * If the branch is a fallback in lieu of the determined default.
         */
        public readonly bool $is_fallback,
    ) {
    }

    public static function new(string $name, bool $is_provided, bool $is_current, bool $is_default, bool $is_fallback): static
    {
        return new static($name, $is_provided, $is_current, $is_default, $is_fallback);
    }

    /**
     * Set the branch as being provided by the user.
     */
    public static function asProvided(string $name): static
    {
        return static::new($name, true, false, false, false);
    }

    /**
     * Set the branch as being the determined current working branch.
     */
    public static function asCurrent(string $name): static
    {
        return static::new($name, false, true, false, false);
    }

    /**
     * Set the branch as being the determined default.
     */
    public static function asDefault(string $name): static
    {
        return static::new($name, false, false, true, false);
    }

    /**
     * Set the branch as being a fallback in lieu of the determined default.
     */
    public static function asFallback(string $name): static
    {
        return static::new($name, false, false, false, true);
    }

    /**
     * Get the branch's provenance as a string
     * if the information is available.
     */
    public function provenance(): ?string
    {
        $value = null;

        if ($this->is_provided) {
            $value = 'provided';
        }

        if ($this->is_current) {
            $value = 'current';
        }

        if ($this->is_default) {
            $value = 'default';
        }

        if ($this->is_fallback) {
            $value = 'fallback';
        }

        return $value;
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'is_provided' => $this->is_provided,
            'is_current' => $this->is_current,
            'is_default' => $this->is_default,
            'is_fallback' => $this->is_fallback,
        ];
    }
}
