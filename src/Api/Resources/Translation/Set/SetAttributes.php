<?php

declare(strict_types=1);

namespace Transl\Api\Resources\Translation\Set;

/**
 * @phpstan-consistent-constructor
 */
class SetAttributes
{
    public function __construct(
        public readonly string $id,
        public readonly string $locale,
        public readonly ?string $group,
        public readonly ?string $namespace,
    ) {
    }

    public static function from(array $attributes): static
    {
        return new static(
            id: $attributes['id'],
            locale: $attributes['locale'],
            group: $attributes['group'],
            namespace: $attributes['namespace'],
        );
    }
}
