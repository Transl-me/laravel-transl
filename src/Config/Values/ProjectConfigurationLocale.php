<?php

declare(strict_types=1);

namespace Transl\Config\Values;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class ProjectConfigurationLocale implements Arrayable
{
    public function __construct(
        /**
         * The project's default locale.
         * Usually used as a reference for other locales.
         */
        public readonly ?string $default,

        /**
         * The project's fallback locale.
         */
        public readonly ?string $fallback,

        /**
         * The locales allowed to be pushed to Transl.
         */
        public readonly ?array $allowed,

        /**
         * Whether to throw or silently ignore encounted
         * locales that are not in the allowed list.
         */
        public readonly bool $throw_on_disallowed_locale,
    ) {
    }

    /**
     * Named constructor.
     */
    public static function new(
        ?string $default,
        ?string $fallback,
        ?array $allowed,
        bool $throw_on_disallowed_locale,
    ): static {
        return new static($default, $fallback, $allowed, $throw_on_disallowed_locale);
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'default' => $this->default,
            'fallback' => $this->fallback,
            'allowed' => $this->allowed,
            'throw_on_disallowed_locale' => $this->throw_on_disallowed_locale,
        ];
    }
}
