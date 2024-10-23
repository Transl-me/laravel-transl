<?php

declare(strict_types=1);

namespace Transl\Config\Values;

use Illuminate\Contracts\Support\Arrayable;
use Transl\Config\Values\ProjectConfigurationLocale;
use Transl\Config\Values\ProjectConfigurationBranching;

/**
 * @implements Arrayable<string, array>
 * @phpstan-consistent-constructor
 */
class ProjectConfigurationOptions implements Arrayable
{
    /**
     * A local directory used to store/cache/track
     * necessary informations.
     */
    public readonly ?string $transl_directory;

    /**
     * The project's locale specific configurations.
     */
    public readonly ProjectConfigurationLocale $locale;

    /**
     * The project's branching specific configurations.
     */
    public readonly ProjectConfigurationBranching $branching;

    public function __construct(
        ?string $transl_directory,
        array|ProjectConfigurationLocale $locale,
        array|ProjectConfigurationBranching $branching,
    ) {
        $this->transl_directory = $transl_directory;

        $this->hydrateLocaleProperty($locale);
        $this->hydrateBranchingProperty($branching);
    }

    /**
     * Named constructor.
     */
    public static function new(
        ?string $transl_directory,
        array|ProjectConfigurationLocale $locale,
        array|ProjectConfigurationBranching $branching,
    ): static {
        return new static($transl_directory, $locale, $branching);
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'transl_directory' => $this->transl_directory,
            'locale' => $this->locale->toArray(),
            'branching' => $this->branching->toArray(),
        ];
    }

    protected function hydrateLocaleProperty(array|ProjectConfigurationLocale $locale): void
    {
        // @phpstan-ignore-next-line
        $this->locale = is_array($locale) ? ProjectConfigurationLocale::new(...$locale) : $locale;
    }

    protected function hydrateBranchingProperty(array|ProjectConfigurationBranching $branching): void
    {
        // @phpstan-ignore-next-line
        $this->branching = is_array($branching) ? ProjectConfigurationBranching::new(...$branching) : $branching;
    }
}
