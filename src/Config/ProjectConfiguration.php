<?php

declare(strict_types=1);

namespace Transl\Config;

use Transl\Config\DefaultConfiguration;
use Transl\Support\Contracts\Driverable;
use Illuminate\Contracts\Support\Arrayable;
use Transl\Config\Values\ProjectConfigurationOptions;
use Transl\Config\ProjectConfigurationDriverCollection;
use Transl\Config\Helpers\MergeProjectConfigurationOptions;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class ProjectConfiguration implements Arrayable
{
    public function __construct(
        /**
         * The project's authentication key.
         * Used to both identify the project and
         * the user making local and remote changes.
         */
        public readonly string $auth_key,

        /**
         * An arbitrary unique value given to identify
         * the project | A user friendly name given to the project.
         *
         * Used when printing the project back to the user
         * in console outputs, exception messages etc... .
         *
         * Falls back to be a truncated and redacted version
         * of the authentication key.
         */
        public readonly string $name,

        /**
         * The project's configuration options.
         * Used to configure behaviors.
         */
        public readonly ProjectConfigurationOptions $options,

        /**
         * The project's configuration drivers.
         * Used for identifying, retrieving, updating and handling
         * translation contents.
         */
        public readonly ProjectConfigurationDriverCollection $drivers,
    ) {
    }

    /**
     * Named constructor.
     */
    public static function new(
        string $auth_key,
        string $name,
        ProjectConfigurationOptions $options,
        ProjectConfigurationDriverCollection $drivers,
    ): static {
        return new static($auth_key, $name, $options, $drivers);
    }

    /**
     * Named constructor accepting an arbitrary array of values and
     * defaults that will be used to contructor a new instance.
     */
    public static function make(array $item, ?DefaultConfiguration $defaults = null): static
    {
        return $defaults ? static::makeWithDefaults($item, $defaults) : static::new(...$item);
    }

    protected static function makeWithDefaults(array $item, DefaultConfiguration $defaults): static
    {
        $item['name'] = $item['name'] ?? static::redactAuthKey($item['auth_key']);

        $item['options'] = MergeProjectConfigurationOptions::mergeWithDefaults(
            $item['options'] ?? [],
            $defaults->project_options,
        );

        /** @var array<class-string<Driverable>|int, array|null> $item['drivers'] */
        $item['drivers'] = ProjectConfigurationDriverCollection::make($item['drivers'] ?? []);

        return static::make($item);
    }

    protected static function redactAuthKey(string $value): string
    {
        $prefixLength = mb_strpos($value, '_') + 1;

        return mb_substr($value, 0, $prefixLength + 5) . '•••••' . mb_substr($value, -3);
    }

    /**
     * The project's label.
     * Used in contexts where the project is printed
     * out to users.
     */
    public function label(): string
    {
        if (str_contains($this->name, '•')) {
            return $this->name;
        }

        return $this->name . ' (' . static::redactAuthKey($this->auth_key) . ')';
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'auth_key' => $this->auth_key,
            'name' => $this->name,
            'options' => $this->options->toArray(),
            'drivers' => $this->drivers->toArray(),
        ];
    }
}
