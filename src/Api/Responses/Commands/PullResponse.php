<?php

declare(strict_types=1);

namespace Transl\Api\Responses\Commands;

use Illuminate\Http\Client\Response;
use Transl\Api\Objects\CursorPagination;
use Transl\Api\Resources\Translation\Set\Set;

/**
 * @phpstan-consistent-constructor
 */
class PullResponse
{
    public function __construct(
        /**
         * @param array<array-key, Set> $data
         */
        public readonly array $data,
        public readonly CursorPagination $pagination,
    ) {
    }

    public static function fromClientResponse(Response $response): static
    {
        /** @var array $data */
        $data = $response->json('data');

        /** @var array $pagination */
        $pagination = $response->json('pagination');

        return new static(
            data: array_map(static fn (array $set): Set => Set::from($set), $data),
            pagination: CursorPagination::from($pagination),
        );
    }
}
