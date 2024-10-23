<?php

declare(strict_types=1);

namespace Transl\Config\Defaults;

use Illuminate\Contracts\Support\Arrayable;
use Transl\Config\Values\ProjectConfigurationLocale;
use Transl\Config\Values\ProjectConfigurationOptions;
use Transl\Config\Values\ProjectConfigurationBranching;
use Transl\Config\Enums\BranchingConflictResolutionEnum;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class DefaultConfigurationValues implements Arrayable
{
    public readonly ?string $project;
    public readonly ProjectConfigurationOptions $project_options;

    public function __construct(array $projects = [])
    {
        $this->project = $this->determineDefaultProjectAuthKey($projects);
        $this->project_options = ProjectConfigurationOptions::new(
            transl_directory: $this->makeDefaultProjectConfigurationTranslDirectory(),
            locale: $this->makeDefaultProjectConfigurationLocale(),
            branching: $this->makeDefaultProjectConfigurationBranching(),
        );
    }

    public static function new(array $projects = []): static
    {
        return new static($projects);
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

    protected function determineDefaultProjectAuthKey(array $projects): ?string
    {
        if (count($projects) !== 1) {
            return null;
        }

        /** @var array $project */
        $project = head($projects);

        return $project['auth_key'] ?? null;
    }

    protected function makeDefaultProjectConfigurationTranslDirectory(): ?string
    {
        return storage_path('app/.transl');
    }

    protected function makeDefaultProjectConfigurationLocale(): ProjectConfigurationLocale
    {
        /** @var string|null $locale */
        $locale = config('app.locale');

        /** @var string|null $fallback */
        $fallback = config('app.fallback_locale');

        return ProjectConfigurationLocale::new(
            default: $locale,
            fallback: $fallback,
            allowed: null,
            throw_on_disallowed_locale: true,
        );
    }

    protected function makeDefaultProjectConfigurationBranching(): ProjectConfigurationBranching
    {
        return ProjectConfigurationBranching::new(
            default_branch_name: null,
            mirror_current_branch: true,
            conflict_resolution: BranchingConflictResolutionEnum::MERGE_BUT_THROW,
        );
    }
}
