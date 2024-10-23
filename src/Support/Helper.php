<?php

declare(strict_types=1);

namespace Transl\Support;

// use Illuminate\Support\Traits\EnumeratesValues;
use Transl\Exceptions\Helper\CouldNotEncodeIntoJson;
use Transl\Exceptions\Helper\CouldNotFindThePositionOfTheFirstOccurrenceOfASubstring;

class Helper
{
    public static function jsonEncode(mixed $value, int $flags = 0): string
    {
        $encoded = json_encode($value, $flags);

        if ($encoded === false) {
            throw CouldNotEncodeIntoJson::make();
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw CouldNotEncodeIntoJson::make();
        }

        return $encoded;
    }

    public static function strpos(string $haystack, string $needle, int $offset = 0): int
    {
        $value = mb_strpos($haystack, $needle, $offset);

        if ($value === false) {
            throw CouldNotFindThePositionOfTheFirstOccurrenceOfASubstring::make($haystack, $needle, $offset);
        }

        return $value;
    }

    // public static function objectToArray(object $value): array
    // {
    //     return static::arrayableItemsToArray($value);
    // }

    // public static function arrayableItemsToArray(mixed $items): array
    // {
    //     return (
    //         new class() {
    //             /**
    //              * @use \Illuminate\Support\Traits\EnumeratesValues<array-key, mixed>
    //              */
    //             use EnumeratesValues;

    //             public function items(mixed $items): array
    //             {
    //                 return $this->getArrayableItems($items);
    //             }
    //         }
    //     )->items($items);
    // }
}
