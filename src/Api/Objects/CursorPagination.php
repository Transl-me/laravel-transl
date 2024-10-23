<?php

declare(strict_types=1);

namespace Transl\Api\Objects;

/**
 * @phpstan-consistent-constructor
 */
class CursorPagination
{
    public function __construct(
        public readonly string $path,
        public readonly int $per_page,
        public readonly bool $has_more_pages,
        public readonly ?string $prev_cursor,
        public readonly ?string $prev_page_url,
        public readonly ?string $next_cursor,
        public readonly ?string $next_page_url,
    ) {
    }

    public static function from(array $data): static
    {
        return new static(...$data['attributes']);
    }
}
