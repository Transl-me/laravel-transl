<?php

declare(strict_types=1);

namespace Transl\Exceptions\ProjectConfiguration;

use Transl\Exceptions\ProjectConfiguration\ProjectConfigurationException;

class MultipleProjectsFound extends ProjectConfigurationException
{
    public static function make(): self
    {
        return static::message('Found multiple projects. Impossible to determine which to use.');
    }

    public static function fromAuthKeyOrName(string $value): self
    {
        return static::message(
            "Found multiple projects with the same provided \"auth_key\" or \"name\" of `{$value}`. Impossible to determine which to use.",
        );
    }
}
