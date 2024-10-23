<?php

declare(strict_types=1);

namespace Transl\Support;

use Transl\Transl;
use Composer\InstalledVersions;
use Illuminate\Foundation\Application;
use Transl\Support\Concerns\Instanciable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, string>
 * @phpstan-consistent-constructor
 */
class Versions implements Arrayable
{
    use Instanciable;

    public function __construct(
        public readonly string $php,
        public readonly string $laravel,
        public readonly string $package,
    ) {
    }

    public static function new(string $php, string $laravel, string $package): static
    {
        return new static($php, $laravel, $package);
    }

    public static function make(): static
    {
        return static::new(
            static::phpVersion(),
            static::laravelVersion(),
            static::packageVersion(),
        );
    }

    public static function instance(): static
    {
        if (!static::hasInstance()) {
            static::setInstance(static::make());
        }

        // @phpstan-ignore-next-line
        return static::getInstance();
    }

    public static function phpVersion(): string
    {
        return PHP_VERSION;
    }

    public static function laravelVersion(): string
    {
        return Application::VERSION;
    }

    public static function packageVersion(): string
    {
        return (string) InstalledVersions::getVersion(Transl::PACKAGE_NAME);
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'php' => $this->php,
            'laravel' => $this->laravel,
            'package' => $this->package,
        ];
    }
}
