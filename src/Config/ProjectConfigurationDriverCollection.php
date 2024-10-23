<?php

declare(strict_types=1);

namespace Transl\Config;

use Illuminate\Support\Collection;
use Transl\Support\Contracts\Driverable;
use Illuminate\Contracts\Support\Arrayable;
use Transl\Exceptions\ProjectConfigurationDriver\ProjectConfigurationDriverInvalidPhpClass;
use Transl\Exceptions\ProjectConfigurationDriver\ProjectConfigurationDriverClassDoesntExist;
use Transl\Exceptions\ProjectConfigurationDriver\ProjectConfigurationDriverDoesntImplementCorrectInterface;

/**
 * @extends Collection<class-string<Driverable>, array>
 */
class ProjectConfigurationDriverCollection extends Collection
{
    /**
     * Create a new collection.
     *
     * @param  Arrayable<class-string<Driverable>, array>|iterable<class-string<Driverable>, array>|null  $items
     * @return void
     */
    public function __construct($items = [])
    {
        $this->items = $this->arrayableItemsToStanderdizedItems($this->getArrayableItems($items));
    }

    /**
     * Converts items into ProjectConfigurations.
     *
     * @param  array<array-key, array>  $items
     * @return array<class-string<Driverable>, array>
     */
    protected function arrayableItemsToStanderdizedItems(array $items): array
    {
        return collect($items)->reduce(function (array $acc, string|array $value, string|int $key): array {
            /** @var string|null $class */
            $class = $this->arrayableItemKeyCanBeUseAsAClassString($key) ? $key : null;
            $parameters = $this->arrayableItemValueCanBeUsedAsClassParameters($value) ? $value : [];

            if (!$class && $this->arrayableItemValueCanBeUseAsAClassString($value)) {
                /** @var string $class */
                $class = $value;
            }

            $this->ensureArrayableItemDriverIsValid($class);

            $acc[$class] = $parameters;

            return $acc;
        }, []);
    }

    protected function arrayableItemKeyCanBeUseAsAClassString(string|int $key): bool
    {
        return is_string($key);
    }

    protected function arrayableItemValueCanBeUseAsAClassString(string|array $value): bool
    {
        return is_string($value);
    }

    protected function arrayableItemValueCanBeUsedAsClassParameters(string|array $value): bool
    {
        return is_array($value);
    }

    protected function ensureArrayableItemDriverIsValid(null|string $class): void
    {
        if (is_null($class)) {
            throw ProjectConfigurationDriverInvalidPhpClass::make();
        }

        if (!class_exists($class)) {
            throw ProjectConfigurationDriverClassDoesntExist::make($class);
        }

        if (!in_array(Driverable::class, class_implements($class) ?: [], true)) {
            throw ProjectConfigurationDriverDoesntImplementCorrectInterface::make($class);
        }
    }
}
