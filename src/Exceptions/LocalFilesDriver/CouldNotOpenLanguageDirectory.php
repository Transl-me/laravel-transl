<?php

declare(strict_types=1);

namespace Transl\Exceptions\LocalFilesDriver;

use Transl\Support\LocaleFilesystem\FilePath;
use Transl\Exceptions\LocalFilesDriver\LocalFilesDriverException;

class CouldNotOpenLanguageDirectory extends LocalFilesDriverException
{
    public static function make(FilePath $directory, string $driverClass): self
    {
        return static::message(
            "The language directory `{$directory->fullPath()}` provided to the driver `{$driverClass}` could not be opened.",
        );
    }
}
