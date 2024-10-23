<?php

declare(strict_types=1);

namespace Transl\Exceptions\Helper;

use Transl\Exceptions\Helper\HelperException;

class CouldNotFindThePositionOfTheFirstOccurrenceOfASubstring extends HelperException
{
    public static function make(string $haystack, string $needle, int $offset): self
    {
        return static::message("Could not find the position of the first occurrence of `{$needle}` in `{$haystack}` given the offset `{$offset}`.");
    }
}
