<?php

declare(strict_types=1);

namespace Transl\Exceptions\Helper;

use Transl\Exceptions\Helper\HelperException;

class CouldNotEncodeIntoJson extends HelperException
{
    public static function make(): self
    {
        return static::message('Error encoding a given value into JSON');
    }
}
