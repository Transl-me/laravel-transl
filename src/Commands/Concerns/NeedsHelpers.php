<?php

declare(strict_types=1);

namespace Transl\Commands\Concerns;

use Transl\Exceptions\Console\TranslConsoleException;

/**
 * @mixin \Illuminate\Console\Command
 */
trait NeedsHelpers
{
    protected function throw(string $message): never
    {
        throw TranslConsoleException::message($message);
    }

    protected function arrayableOptionValues(?array $values): array
    {
        return collect($values)
            ->filter(static fn (mixed $value) => !blank($value))
            ->flatMap(static fn (string $value) => explode(',', $value))
            ->map(static fn (string $value) => trim($value))
            ->filter(static fn (mixed $value) => !blank($value))
            ->unique()
            ->values()
            ->all();
    }

    /* Command option retrieval helpers
    ------------------------------------------------*/

    protected function optionAsNullableString(string $key): ?string
    {
        $value = $this->option($key);

        if (is_null($value) || is_string($value)) {
            return $value;
        }

        $this->throw("The given option `{$key}` is of an invalid type. Valid types are: `string`, `null`.");
    }

    protected function optionAsNullableArray(string $key): ?array
    {
        $value = $this->option($key);

        if (is_null($value) || is_array($value)) {
            return $value;
        }

        $this->throw("The given option `{$key}` is of an invalid type. Valid types are: `array`, `null`.");
    }
}
