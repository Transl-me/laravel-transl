<?php

declare(strict_types=1);

namespace Transl\Support\Reports\MissingTranslationKeys;

use Throwable;
use Transl\Support\Git;
use Transl\Facades\Transl;
use Transl\Support\Branch;
use Transl\Exceptions\TranslException;
use Transl\Config\ProjectConfiguration;
use Transl\Actions\Reports\SendMissingTranslationKeyReportAction;
use Transl\Exceptions\ProjectConfiguration\MultipleProjectsFound;
use Transl\Exceptions\ProjectConfiguration\CouldNotDetermineProject;
use Transl\Exceptions\Report\MissingTranslationKeys\CouldNotBuildReport;
use Transl\Support\Reports\MissingTranslationKeys\MissingTranslationKey;
use Transl\Support\Reports\MissingTranslationKeys\MissingTranslationKeyReport;

class MissingTranslationKeys
{
    /**
     * The missing translation key reports to report.
     *
     * @var array<string, MissingTranslationKeyReport>
     */
    protected array $queue = [];

    public function __construct()
    {
        register_shutdown_function([$this, 'report']);
    }

    /**
     * Queue up a missing translation key to be reported.
     */
    public function add(
        string $key,
        array $replacements,
        string $locale,
        bool $fallback,
        ProjectConfiguration|string|null $project = null,
        Branch|string|null $branch = null,
    ): static {
        return $this->register(
            MissingTranslationKey::new($key, $replacements, $locale, $fallback),
            $project,
            $branch,
        );
    }

    /**
     * Queue up a missing translation key object to be reported.
     */
    public function register(
        MissingTranslationKey $key,
        ProjectConfiguration|string|null $project = null,
        Branch|string|null $branch = null,
    ): static {
        $report = $this->tryMakeReport($project, $branch, $key);

        if (!$report) {
            return $this;
        }

        return $this->queue($report);
    }

    /**
     * Queue up a missing translation key report.
     */
    public function queue(MissingTranslationKeyReport $report): static
    {
        $this->queue[$report->id()] = $report;

        return $this;
    }

    /**
     * Set the missing translation key reports to report.
     *
     * @param array<array-key, MissingTranslationKeyReport> $queue
     */
    public function setQueue(array $queue): static
    {
        foreach ($queue as $report) {
            $this->queue($report);
        }

        return $this;
    }

    public function flushQueue(): static
    {
        $this->queue = [];

        return $this;
    }

    /**
     * Retrieve the queued missing translation key reports.
     *
     * @return array<string, MissingTranslationKeyReport>
     */
    public function queued(): array
    {
        return $this->queue;
    }

    /**
     * Send the queued missing translation key reports.
     */
    public function report(): void
    {
        $queued = $this->queued();

        if (empty($queued)) {
            return;
        }

        try {
            app(SendMissingTranslationKeyReportAction::class)->execute($queued);
        } catch (Throwable $th) {
            if ($this->shouldFailSilently()) {
                return;
            }

            throw $th;
        } finally {
            $this->flushQueue();
        }
    }

    /* Helpers
    ------------------------------------------------*/

    protected function tryMakeReport(
        ProjectConfiguration|string|null $project,
        Branch|string|null $branch,
        MissingTranslationKey $key,
    ): ?MissingTranslationKeyReport {
        try {
            return $this->makeReport($project, $branch, $key);
        } catch (TranslException $exception) {
            if ($this->shouldFailSilently()) {
                return null;
            }

            throw $exception;
        }
    }

    protected function makeReport(
        ProjectConfiguration|string|null $project,
        Branch|string|null $branch,
        MissingTranslationKey $key,
    ): MissingTranslationKeyReport {
        if (!$project) {
            $project = $this->guessProject();
        }

        if (is_string($project)) {
            $project = $this->findProject($project);
        }

        if (!$branch) {
            $branch = $this->guessBranch($project);
        }

        if (is_string($branch)) {
            $branch = $this->makeBranchFromString($branch);
        }

        if (!$project) {
            throw CouldNotBuildReport::fromMissingProject($project);
        }

        if (!$branch) {
            throw CouldNotBuildReport::fromMissingBranch($branch);
        }

        return MissingTranslationKeyReport::new($project, $branch, $key);
    }

    protected function findProject(string $project): ProjectConfiguration
    {
        $projects = Transl::config()->projects()->whereAuthKeyOrName($project);

        if ($projects->isEmpty()) {
            throw CouldNotDetermineProject::fromAuthKeyOrName($project);
        }

        if ($projects->count() > 1) {
            throw MultipleProjectsFound::fromAuthKeyOrName($project);
        }

        return $projects->first();
    }

    protected function guessProject(): ?ProjectConfiguration
    {
        $projects = Transl::config()->projects();

        if ($projects->count() > 1) {
            throw MultipleProjectsFound::make();
        }

        return $projects->first();
    }

    protected function makeBranchFromString(string $branch): Branch
    {
        return Branch::asProvided($branch);
    }

    protected function guessBranch(?ProjectConfiguration $project): ?Branch
    {
        $branch = null;

        if ($project && $project->options->branching->mirror_current_branch) {
            $value = Git::currentBranchName();
            $branch = $value ? Branch::asCurrent(trim($value)) : null;
        }

        if ($project && !$branch?->name) {
            $value = $project->options->branching->default_branch_name ?: Git::defaultConfiguredBranchName();
            $branch = $value ? Branch::asDefault(trim($value)) : null;
        }

        if (!$branch?->name) {
            $branch = Branch::asFallback(Transl::FALLBACK_BRANCH_NAME);
        }

        return $branch;
    }

    protected function shouldFailSilently(): bool
    {
        return Transl::config()->reporting()->silently_discard_exceptions;
    }
}
