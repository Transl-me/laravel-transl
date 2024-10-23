<?php

declare(strict_types=1);

namespace Transl\Support;

use Illuminate\Contracts\Support\Arrayable;
use Transl\Support\TranslationLinesDiffing;
use Transl\Support\TranslationLineCollection;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class TranslationSet implements Arrayable
{
    public function __construct(
        public readonly string $locale,
        public readonly ?string $group,
        public readonly ?string $namespace,
        public readonly TranslationLineCollection $lines,
        public readonly ?array $meta,
    ) {
    }

    /**
     * Named constructor.
     */
    public static function new(
        string $locale,
        ?string $group,
        ?string $namespace,
        TranslationLineCollection $lines,
        ?array $meta,
    ): static {
        return app(static::class, [
            'locale' => $locale,
            'group' => $group,
            'namespace' => $namespace,
            'lines' => $lines,
            'meta' => $meta,
        ]);
    }

    /**
     * Named constructor accepting an arbitrary array of values that
     * will be used to contructor a new instance.
     */
    public static function from(array $properties): static
    {
        if (!($properties['lines'] instanceof TranslationLineCollection)) {
            /** @var array<array-key, array|TranslationLine> $properties['lines'] */
            $properties['lines'] = TranslationLineCollection::make($properties['lines']);
        }

        return static::new(...$properties);
    }

    /**
     * Constructs a new instance of `TranslationLinesDiffing`
     * with the lines of the current instance as the incoming lines
     * from which to differenciate.
     */
    public function diff(
        TranslationLineCollection|TranslationSet $trackedLines,
        TranslationLineCollection|TranslationSet $currentLines,
    ): TranslationLinesDiffing {
        if ($trackedLines instanceof TranslationSet) {
            $trackedLines = $trackedLines->lines;
        }

        if ($currentLines instanceof TranslationSet) {
            $currentLines = $currentLines->lines;
        }

        return TranslationLinesDiffing::new(
            trackedLines: $trackedLines,
            currentLines: $currentLines,
            incomingLines: $this->lines,
        );
    }

    /**
     * tl;dr note: Having multiple JSON files for the same locale = BAD
     * but expected behavior.
     *
     * Longer note: Having multiple JSON files for the same locale,
     * when using the provided `LocalFilesDriver` driver,
     * will result in a single and same tracking key for all
     * JSON files; no matter nested or not.
     *
     * This is because all JSON files result in a null group
     * and a null namespace. Thus, the only possible differentiator
     * is the locale, but they would all have the same and
     * therefore result in the same tracking key.
     *
     * This is the expected behavior as that's exacly how Laravel's
     * `vendor/laravel/framework/src/Illuminate/Translation/FileLoader.php`
     * handles JSON translation files, with the difference that `*` is the
     * value of the group and the namespace.
     *
     * When looping through the available translation files the lines
     * attached to the resulting tracking key should in theory be that of
     * the last JSON file in the loop.
     */
    public function trackingKey(): string
    {
        $key = "locales/{$this->locale}";

        if ($this->group) {
            $key = "groups/{$this->group}/{$key}";
        }

        if ($this->namespace) {
            $key = "namespaces/{$this->namespace}/{$key}";
        }

        return $key;
    }

    /**
     * The key used in Laravel translation helper functions
     * like `__` or `trans`.
     *
     * Will return an empty string when the translation set
     * has no group and no namespace.
     */
    public function translationKey(): string
    {
        $key = (string) $this->group;

        if ($this->group && $this->namespace) {
            $key = "{$this->namespace}::{$key}";
        }

        return $key;
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'locale' => $this->locale,
            'group' => $this->group,
            'namespace' => $this->namespace,
            'lines' => $this->lines->toArray(),
            'meta' => $this->meta,
        ];
    }
}
