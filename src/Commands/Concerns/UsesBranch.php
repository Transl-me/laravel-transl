<?php

declare(strict_types=1);

namespace Transl\Commands\Concerns;

use Transl\Support\Git;
use Transl\Facades\Transl;
use Transl\Support\Branch;

/**
 * @mixin \Illuminate\Console\Command
 */
trait UsesBranch
{
    /**
     * The branch on the defined project to target.
     */
    protected Branch $branch;

    /* Methods
    ------------------------------------------------*/

    protected function hydrateBranchProperty(?string $value): void
    {
        $branch = $value ? Branch::asProvided(trim($value)) : null;

        if (!$branch?->name && $this->project->options->branching->mirror_current_branch) {
            $value = Git::currentBranchName();
            $branch = $value ? Branch::asCurrent(trim($value)) : null;
        }

        if (!$branch?->name) {
            $value = $this->project->options->branching->default_branch_name ?: Git::defaultConfiguredBranchName();
            $branch = $value ? Branch::asDefault(trim($value)) : null;
        }

        if (!$branch?->name) {
            $branch = Branch::asFallback(Transl::FALLBACK_BRANCH_NAME);
        }

        $this->branch = $branch;
    }
}
