<?php

declare(strict_types=1);

namespace Transl\Exceptions\ProjectConfigurationDriver;

use Transl\Support\Contracts\Driverable;
use Transl\Exceptions\ProjectConfigurationDriver\InvalidProjectConfigurationDriverClass;

class ProjectConfigurationDriverInvalidPhpClass extends InvalidProjectConfigurationDriverClass
{
    public static function make(): self
    {
        $contract = Driverable::class;

        return static::message("A non PHP class has been provided instead of a class implementing `{$contract}`.");
    }
}
