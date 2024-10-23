<?php

declare(strict_types=1);

namespace Transl\Exceptions\ProjectConfiguration;

use Transl\Exceptions\ProjectConfiguration\ProjectConfigurationException;

class CouldNotDetermineProject extends ProjectConfigurationException
{
    // public static function make(): self
    // {
    //     return static::message(
    //         'No project could be determined. Please provide the "auth_key" of an existing project on Transl.me or the "name" of a configured project.'
    //     );
    // }

    public static function fromAuthKeyOrName(string $value): self
    {
        return static::message(
            "No project could be determined from the provided value `{$value}`. Please provide the \"auth_key\" of an existing project on Transl.me or the \"name\" of a configured project.",
        );
    }
}
