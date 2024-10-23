<?php

declare(strict_types=1);

namespace Transl\Support\Reports\MissingTranslationKeys;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class MissingTranslationKey implements Arrayable
{
    public function __construct(
        public readonly string $value,
        public readonly array $replacements,
        public readonly string $locale,
        public readonly bool $fallback,
    ) {
    }

    public static function new(string $value, array $replacements, string $locale, bool $fallback): static
    {
        return new static($value, $replacements, $locale, $fallback);
    }

    public function id(): string
    {
        return "{$this->locale}:{$this->value}";
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'replacements' => $this->replacements,
            'locale' => $this->locale,
            'fallback' => $this->fallback,
        ];
    }
}
