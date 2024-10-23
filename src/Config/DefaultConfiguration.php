<?php

declare(strict_types=1);

namespace Transl\Config;

use Illuminate\Contracts\Support\Arrayable;
use Transl\Config\Values\ProjectConfigurationOptions;
use Transl\Config\Defaults\DefaultConfigurationValues;
use Transl\Config\Helpers\MergeProjectConfigurationOptions;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class DefaultConfiguration implements Arrayable
{
    /**
     * The default project to be considered in contexts
     * where a project is needed but none where explicitly
     * provided.
     */
    public readonly ?string $project;

    /**
     * Default project options that will be used in filling
     * a given project's option that hasn't been given a value.
     * In other words, fallback option values for a given project.
     *
     * The exact same as `project.options`.
     */
    public readonly ProjectConfigurationOptions $project_options;

    public function __construct(array $values, DefaultConfigurationValues $defaults)
    {
        $this->hydrateProperties($values, $defaults);
    }

    /**
     * Named constructor.
     */
    public static function new(array $values, DefaultConfigurationValues $defaults): static
    {
        return new static($values, $defaults);
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'project' => $this->project,
            'project_options' => $this->project_options->toArray(),
        ];
    }

    protected function hydrateProperties(array $values, DefaultConfigurationValues $defaults): void
    {
        $this->hydrateProjectProperty($values, $defaults);
        $this->hydrateProjectOptionsProperty($values, $defaults);
    }

    protected function hydrateProjectProperty(array $values, DefaultConfigurationValues $defaults): void
    {
        // @phpstan-ignore-next-line
        $this->project = $values['project'] ?? $defaults->project;
    }

    protected function hydrateProjectOptionsProperty(array $values, DefaultConfigurationValues $defaults): void
    {
        // @phpstan-ignore-next-line
        $this->project_options = MergeProjectConfigurationOptions::mergeWithDefaults(
            $values['project_options'] ?? [],
            $defaults->project_options,
        );
    }
}
