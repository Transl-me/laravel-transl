<?php

declare(strict_types=1);

namespace Transl\Support\Concerns;

trait Instanciable
{
    protected static ?self $instance = null;

    public static function instance(mixed ...$args): static
    {
        if (!static::hasInstance()) {
            static::setInstance(static::newInstance(...$args));
        }

        // @phpstan-ignore-next-line
        return static::getInstance();
    }

    public static function refreshInstance(mixed ...$args): static
    {
        return static::setInstance(static::newInstance(...$args));
    }

    public static function hasInstance(): bool
    {
        return static::$instance !== null;
    }

    public static function newInstance(mixed ...$args): static
    {
        // @phpstan-ignore-next-line
        return new static(...$args);
    }

    public static function setInstance(self $instance): static
    {
        static::$instance = $instance;

        // @phpstan-ignore-next-line
        return static::getInstance();
    }

    public static function getInstance(): ?static
    {
        // @phpstan-ignore-next-line
        return static::$instance;
    }
}
