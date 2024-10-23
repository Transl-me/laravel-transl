<?php

declare(strict_types=1);

namespace Transl\Support;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class TranslationLine implements Arrayable
{
    public function __construct(
        /**
         * The translation line key without the group.
         * Example: `/lang/en/validation.php` -> `attributes.email.required`.
         */
        public readonly string $key,

        /**
         * The translation line value.
         * The value that is to be translated.
         */
        public readonly null|string|int|float|bool $value,

        /**
         * Optional metadata about the translation line.
         */
        public readonly ?array $meta,
    ) {
    }

    /**
     * Named constructor.
     */
    public static function new(string $key, null|string|int|float|bool $value, ?array $meta): static
    {
        return new static($key, $value, $meta);
    }

    /**
     * Named constructor accepting an undetermined `$value` type
     * that will be standardized into one of the supported types
     * (`null`|`string`|`int`|`float`|`bool`).
     */
    public static function make(string $key, mixed $value, ?array $meta): static
    {
        return static::from([
            'key' => $key,
            'value' => $value,
            'meta' => $meta,
        ]);
    }

    /**
     * Named constructor accepting an arbitrary array of values that
     * will be used to contructor a new instance.
     */
    public static function from(array $properties): static
    {
        return static::new(...static::standardizePropertiesValues($properties));
    }

    /**
     * Standardize the property values of the to be constructed
     * instance.
     */
    protected static function standardizePropertiesValues(array $properties): array
    {
        [
            'key' => $key,
            'value' => $value,
            'meta' => $meta,
        ] = $properties;

        $meta = [
            ...($meta ?: []),
            'original_value_type' => ($meta ?: [])['original_value_type'] ?? gettype($value),
        ];

        if (is_array($value) && empty($value)) {
            $value = null;
        }

        if (!is_null($value) && !is_bool($value) && !is_numeric($value)) {
            $value = (string) $value;
        }

        return [
            'key' => $key,
            'value' => $value,
            'meta' => $meta,
        ];
    }

    /**
     * Try, as best as possible with available metadata to reconstruct
     * back the original value of the translation line.
     */
    public function potentialOriginalValue(): mixed
    {
        $originalValueType = ($this->meta ?: [])['original_value_type'] ?? null;

        if ($originalValueType === 'array' && is_null($this->value)) {
            return [];
        }

        return $this->value;
    }

    /**
     * Convert the translation lines value into a string.
     * Hopefully better than PHP's default type juggling.
     */
    public function valueAsString(): string
    {
        if ($this->value === true) {
            return 'true';
        }

        if ($this->value === false) {
            return 'false';
        }

        return (string) $this->value;
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'meta' => $this->meta,
        ];
    }
}
