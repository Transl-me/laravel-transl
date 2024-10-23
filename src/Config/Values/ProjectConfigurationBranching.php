<?php

declare(strict_types=1);

namespace Transl\Config\Values;

use Illuminate\Contracts\Support\Arrayable;
use Transl\Config\Enums\BranchingConflictResolutionEnum;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class ProjectConfigurationBranching implements Arrayable
{
    /**
     * The default branch name to use in contexts where
     * none was provided and/or none could be determined
     * either because of limitations or configurations.
     */
    public readonly ?string $default_branch_name;

    /**
     * Whether local Git branches, when pushing translation
     * lines to Transl, should be reflected on Transl.
     */
    public readonly bool $mirror_current_branch;

    /**
     * How detected conflicts should be handled.
     */
    public readonly BranchingConflictResolutionEnum $conflict_resolution;

    public function __construct(
        bool $mirror_current_branch,
        ?string $default_branch_name,
        string|BranchingConflictResolutionEnum $conflict_resolution,
    ) {
        $this->default_branch_name = $default_branch_name;
        $this->mirror_current_branch = $mirror_current_branch;
        $this->conflict_resolution = is_string($conflict_resolution)
            ? BranchingConflictResolutionEnum::from($conflict_resolution)
            : $conflict_resolution;
    }

    /**
     * Named constructor.
     */
    public static function new(
        ?string $default_branch_name,
        bool $mirror_current_branch,
        string|BranchingConflictResolutionEnum $conflict_resolution,
    ): static {
        return new static($mirror_current_branch, $default_branch_name, $conflict_resolution);
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'default_branch_name' => $this->default_branch_name,
            'mirror_current_branch' => $this->mirror_current_branch,
            'conflict_resolution' => $this->conflict_resolution->value,
        ];
    }
}
