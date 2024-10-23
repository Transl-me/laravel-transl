<?php

declare(strict_types=1);

namespace Transl\Exceptions\LocalFilesDriver;

use Transl\Support\TranslationSet;
use Transl\Exceptions\LocalFilesDriver\LocalFilesDriverException;

class CouldNotDetermineTranslationFileRelativePathFromTranslationSet extends LocalFilesDriverException
{
    public static function make(TranslationSet $set, string $driverClass): self
    {
        $setAsString = "locale:`{$set->locale}`";

        if ($set->group) {
            $setAsString .= ", group:`{$set->group}`";
        }

        if ($set->namespace) {
            $setAsString .= ", namespace:`{$set->namespace}`";
        }

        return static::message(
            "Could not determine a translation file's relative path for the translation set [{$setAsString}] provided to the driver `{$driverClass}`.",
        );
    }
}
