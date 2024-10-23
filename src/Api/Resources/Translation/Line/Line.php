<?php

declare(strict_types=1);

namespace Transl\Api\Resources\Translation\Line;

use Transl\Api\Resources\Translation\Line\LineAttributes;

/**
 * @phpstan-consistent-constructor
 */
class Line
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly LineAttributes $attributes,
        public readonly ?array $meta,
    ) {
    }

    public static function from(array $data): static
    {
        return new static(
            id: $data['id'],
            type: $data['type'],
            attributes: LineAttributes::from($data['attributes']),
            meta: $data['meta'],
        );
    }
}
