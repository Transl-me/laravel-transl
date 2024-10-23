<?php

declare(strict_types=1);

namespace Transl\Exceptions\LocalFilesDriver;

use Transl\Config\ProjectConfiguration;
use Transl\Exceptions\LocalFilesDriver\LocalFilesDriverException;

class FoundDisallowedProjectLocale extends LocalFilesDriverException
{
    public static function make(string $found, ProjectConfiguration $project): self
    {
        $allowed = $project->options->locale->allowed;
        $message = "Encountered a locale `{$found}` that is not in the `options.locale.allowed` array of the project `{$project->name}`.";

        if (!empty($allowed)) {
            $allowed = implode(', ', $allowed);

            $message .= " Allowed locales are: `{$allowed}`.";
        }

        return static::message($message);
    }
}
