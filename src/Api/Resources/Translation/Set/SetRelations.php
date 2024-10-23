<?php

declare(strict_types=1);

namespace Transl\Api\Resources\Translation\Set;

use Transl\Api\Resources\Translation\Line\Line;

/**
 * @phpstan-consistent-constructor
 */
class SetRelations
{
    public function __construct(
        /**
         * @param array<array-key, Line> $lines
         */
        public readonly array $lines,
    ) {
    }

    /**
     * @param array{
     *    lines: array
     * } $relations
     */
    public static function from(array $relations): static
    {
        return new static(
            lines: array_map(static fn (array $line): Line => Line::from($line), $relations['lines']['data']),
        );
    }
}
