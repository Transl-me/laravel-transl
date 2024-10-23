<?php

declare(strict_types=1);

namespace Transl\Api\Resources\Translation\Set;

use Transl\Api\Resources\Translation\Set\SetRelations;
use Transl\Api\Resources\Translation\Set\SetAttributes;

/**
 * @phpstan-consistent-constructor
 */
class Set
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly SetAttributes $attributes,
        public readonly SetRelations $relations,
        public readonly ?array $meta,
    ) {
    }

    public static function from(array $data): static
    {
        return new static(
            id: $data['id'],
            type: $data['type'],
            attributes: SetAttributes::from($data['attributes']),
            relations: SetRelations::from($data['relations']),
            meta: $data['meta'],
        );
    }
}
