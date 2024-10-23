<?php

declare(strict_types=1);

namespace Transl\Exceptions\LocalFilesDriver;

use Transl\Exceptions\LocalFilesDriver\LocalFilesDriverException;

class MissingRequiredTranslationSetGroup extends LocalFilesDriverException
{
    public static function make(string $driverClass, string $method): self
    {
        return static::message(
            "The method `{$method}` of the driver `{$driverClass}` requires a translation set \"group\" to be defined. Have you provided a translation namespace without also providing a group?",
        );
    }
}
