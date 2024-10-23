<?php

declare(strict_types=1);

namespace Transl\Config;

use Illuminate\Support\Collection;
use Transl\Config\DefaultConfiguration;
use Transl\Config\ProjectConfiguration;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @extends Collection<array-key, ProjectConfiguration>
 * @phpstan-consistent-constructor
 */
class ProjectConfigurationCollection extends Collection
{
    /**
     * Create a new collection.
     *
     * @param  Arrayable<array-key, ProjectConfiguration>|iterable<array-key, ProjectConfiguration>|null  $items
     * @return void
     */
    public function __construct($items = [])
    {
        $this->items = $this->arrayableItemsToProjectConfigurations($this->getArrayableItems($items));
    }

    /**
     * Create a new collection instance with defaults
     * if the value isn't one already.
     *
     * @param  iterable<array-key, array>  $items
     */
    public static function makeWithDefaults(iterable $items, DefaultConfiguration $defaults): static
    {
        $items = collect($items)
            ->map(static fn (array $project): ProjectConfiguration => ProjectConfiguration::make($project, $defaults))
            ->all();

        return new static($items);
    }

    /**
     * Find `ProjectConfiguration`s by a given "auth_key".
     */
    public function whereAuthKey(string $authKey): static
    {
        return $this->where('auth_key', $authKey);
    }

    /**
     * Find `ProjectConfiguration`s by a given "name".
     */
    public function whereName(string $name): static
    {
        return $this->where('name', $name);
    }

    /**
     * Find `ProjectConfiguration`s by a given "auth_key" or "name".
     */
    public function whereAuthKeyOrName(string $key): static
    {
        $byAuthKey = $this->whereAuthKey($key);

        if ($byAuthKey->isNotEmpty()) {
            return $byAuthKey;
        }

        return $this->whereName($key);
    }

    // /**
    //  * Find the first possible `ProjectConfiguration` by it's "auth_key".
    //  */
    // public function firstWhereAuthKey(string $authKey): ?ProjectConfiguration
    // {
    //     return $this->firstWhere('auth_key', $authKey);
    // }

    // /**
    //  * Find the first possible `ProjectConfiguration` by it's "name".
    //  */
    // public function firstWhereName(string $name): ?ProjectConfiguration
    // {
    //     return $this->firstWhere('name', $name);
    // }

    // /**
    //  * Find the first possible `ProjectConfiguration` by it's "auth_key"
    //  * or throw an exception.
    //  */
    // public function firstByAuthKeyOrFail(string $authKey): ProjectConfiguration
    // {
    //     return $this->firstOrFail('auth_key', $authKey);
    // }

    // /**
    //  * Find the first possible `ProjectConfiguration` by it's "name"
    //  * or throw an exception.
    //  */
    // public function firstByNameOrFail(string $name): ProjectConfiguration
    // {
    //     return $this->firstOrFail('name', $name);
    // }

    // /**
    //  * Find a `ProjectConfiguration` by it's "auth_key" and
    //  * ensure only one exists or throw an exception.
    //  */
    // public function soleByAuthKey(string $authKey): ProjectConfiguration
    // {
    //     return $this->sole('auth_key', $authKey);
    // }

    // /**
    //  * Find a `ProjectConfiguration` by it's "name" and
    //  * ensure only one exists or throw an exception.
    //  */
    // public function soleByName(string $name): ProjectConfiguration
    // {
    //     return $this->sole('name', $name);
    // }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array<array-key, array>
     */
    public function toArray(): array
    {
        return array_map(static fn (ProjectConfiguration $project): array => $project->toArray(), $this->items);
    }

    /**
     * Converts items into ProjectConfigurations.
     *
     * @param  array<array-key, array|ProjectConfiguration>  $items
     * @return array<array-key, ProjectConfiguration>
     */
    protected function arrayableItemsToProjectConfigurations(array $items): array
    {
        return array_reduce($items, function (array $acc, array|ProjectConfiguration $item): array {
            if (!($item instanceof ProjectConfiguration)) {
                $item = $this->arrayableItemToProjectConfiguration($item);
            }

            $acc[] = $item;

            return $acc;
        }, []);
    }

    /**
     * Converts an item into a ProjectConfiguration.
     */
    protected function arrayableItemToProjectConfiguration(array $item): ProjectConfiguration
    {
        return ProjectConfiguration::make($item);
    }
}
