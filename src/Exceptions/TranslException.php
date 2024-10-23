<?php

declare(strict_types=1);

namespace Transl\Exceptions;

use Exception;

abstract class TranslException extends Exception
{
    public static function message(string $message): static
    {
        return static::new(static::wrapMessage($message));
    }

    protected static function new(mixed ...$args): static
    {
        // @phpstan-ignore-next-line
        return new static(...$args);
    }

    protected static function wrapMessage(string $message): string
    {
        return "[Transl]: {$message}";
    }
}
