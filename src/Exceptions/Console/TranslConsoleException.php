<?php

declare(strict_types=1);

namespace Transl\Exceptions\Console;

use Transl\Exceptions\TranslException;
use Symfony\Component\Console\Exception\ExceptionInterface;

/**
 * Note: Implementing the `ExceptionInterface` instructs
 * Symfony's console component to not output the full
 * command stack trace.
 */
class TranslConsoleException extends TranslException implements ExceptionInterface
{
    //
}
