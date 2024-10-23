<?php

declare(strict_types=1);

namespace Transl\Exceptions\ProjectConfigurationDriver;

use Transl\Support\Contracts\Driverable;
use Transl\Exceptions\ProjectConfigurationDriver\InvalidProjectConfigurationDriverClass;

class ProjectConfigurationDriverDoesntImplementCorrectInterface extends InvalidProjectConfigurationDriverClass
{
    public static function make(string $class): self
    {
        $contract = Driverable::class;

        return static::message("The provided driver class `{$class}` does not implement the `{$contract}` interface.");
    }
}
