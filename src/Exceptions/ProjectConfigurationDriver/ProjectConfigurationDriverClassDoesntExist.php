<?php

declare(strict_types=1);

namespace Transl\Exceptions\ProjectConfigurationDriver;

use Transl\Exceptions\ProjectConfigurationDriver\InvalidProjectConfigurationDriverClass;

class ProjectConfigurationDriverClassDoesntExist extends InvalidProjectConfigurationDriverClass
{
    public static function make(string $class): self
    {
        return static::message("The provided driver class `{$class}` does not exist.");
    }
}
