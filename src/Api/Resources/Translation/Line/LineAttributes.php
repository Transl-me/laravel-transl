<?php

declare(strict_types=1);

namespace Transl\Api\Resources\Translation\Line;

/**
 * @phpstan-consistent-constructor
 */
class LineAttributes
{
    public function __construct(
        public readonly string $id,
        public readonly string $key,
        public readonly null|string|int|float|bool|array $value,
    ) {
    }

    public static function from(array $attributes): static
    {
        return new static(
            id: $attributes['id'],
            key: $attributes['key'],
            value: $attributes['value'],
        );
    }
}
