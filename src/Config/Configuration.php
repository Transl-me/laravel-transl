<?php

declare(strict_types=1);

namespace Transl\Config;

use Transl\Config\DefaultConfiguration;
use Transl\Config\ReportingConfiguration;
use Transl\Support\Concerns\Instanciable;
use Illuminate\Contracts\Support\Arrayable;
use Transl\Config\ProjectConfigurationCollection;
use Transl\Config\Defaults\DefaultConfigurationValues;
use Transl\Config\Defaults\ReportingConfigurationValues;

/**
 * @implements Arrayable<string, array>
 * @phpstan-consistent-constructor
 */
class Configuration implements Arrayable
{
    use Instanciable;

    protected ReportingConfiguration $reporting;
    protected DefaultConfiguration $defaults;
    protected ProjectConfigurationCollection $projects;

    public function __construct(array $config)
    {
        $this->hydrateProperties($config);
    }

    /**
     * Named constructor.
     */
    public static function new(array $config): static
    {
        return new static($config);
    }

    /**
     * The reporting configurations.
     */
    public function reporting(): ReportingConfiguration
    {
        return $this->reporting;
    }

    /**
     * The defaults configurations.
     */
    public function defaults(): DefaultConfiguration
    {
        return $this->defaults;
    }

    /**
     * The projects configurations.
     */
    public function projects(): ProjectConfigurationCollection
    {
        return $this->projects;
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'reporting' => $this->reporting()->toArray(),
            'defaults' => $this->defaults()->toArray(),
            'projects' => $this->projects()->toArray(),
        ];
    }

    protected function hydrateProperties(array $config): void
    {
        $projects = $config['projects'] ?? [];

        $this->reporting = ReportingConfiguration::new($config['reporting'] ?? [], ReportingConfigurationValues::new());
        $this->defaults = DefaultConfiguration::new($config['defaults'] ?? [], DefaultConfigurationValues::new($projects));
        $this->projects = ProjectConfigurationCollection::makeWithDefaults($projects, $this->defaults);
    }
}
