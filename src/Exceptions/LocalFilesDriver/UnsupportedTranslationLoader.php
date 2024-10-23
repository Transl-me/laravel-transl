<?php

declare(strict_types=1);

namespace Transl\Exceptions\LocalFilesDriver;

use Transl\Exceptions\LocalFilesDriver\LocalFilesDriverException;

class UnsupportedTranslationLoader extends LocalFilesDriverException
{
    public static function make(string $loaderClass, string $driverClass, string $supportedLoaderClass): self
    {
        return static::message(
            "The provided translation loader `{$loaderClass}` is not supported by the driver `{$driverClass}`. The only translation loader supported is: `{$supportedLoaderClass}`.",
        );
    }
}
