<?php

declare(strict_types=1);

namespace Transl\Actions\Commands;

use Closure;
use Transl\Support\Git;
use Transl\Facades\Transl;
use Transl\Support\Branch;
use Transl\Support\Push\PushBatch;
use Transl\Config\ProjectConfiguration;
use Transl\Support\Contracts\Driverable;
use Transl\Support\Analysis\ProjectAnalysis;
use Transl\Actions\Commands\PushCommandAction;
use Transl\Actions\Commands\AbstractCommandAction;

class InitCommandAction extends AbstractCommandAction
{
    /**
     * Execute the action.
     */
    public function execute(ProjectConfiguration $project, Branch $branch, ?PushBatch $batch = null, array $meta = []): void
    {
        $this->usingProject($project);
        $this->usingBranch($branch);

        $this->startInitializationOnTransl();

        $meta = [
            ...$meta,
            ...$this->meta(),
        ];

        $this
            ->pushCommandAction()
            ->acceptsLocales($this->onlyLocales)
            ->acceptsGroups($this->onlyGroups)
            ->acceptsNamespaces($this->onlyNamespaces)
            ->rejectsLocales($this->exceptLocales)
            ->rejectsGroups($this->exceptGroups)
            ->rejectsNamespaces($this->exceptNamespaces)
            ->onTranslationSetSkipped($this->translationSetSkippedCallback ?: $this->noop())
            ->onTranslationSetHandled($this->translationSetHandledCallback ?: $this->noop())
            ->execute($project, $branch, $batch, $meta);

        $this->endInitializationOnTransl();
    }

    /* Actions
    ------------------------------------------------*/

    protected function startInitializationOnTransl(): void
    {
        Transl::api()->commands()->initStart(
            $this->project(),
            $this->branch(),
            $this->determineDefaultBranchName(),
        );
    }

    protected function endInitializationOnTransl(): void
    {
        Transl::api()->commands()->initEnd(
            $this->project(),
            $this->branch(),
        );
    }

    protected function pushCommandAction(): PushCommandAction
    {
        return app(PushCommandAction::class);
    }

    protected function meta(): array
    {
        return [
            'unique_translation_key_count' => $this->getTotalUniqueTranslationKeyCount(),
        ];
    }

    /* Helpers
    ------------------------------------------------*/

    protected function determineDefaultBranchName(): Branch
    {
        $branch = $this->project()->options->branching->default_branch_name ?: Git::defaultConfiguredBranchName();

        if ($branch) {
            return Branch::asDefault(trim($branch));
        }

        return Branch::asFallback(Transl::FALLBACK_BRANCH_NAME);
    }

    protected function noop(): Closure
    {
        return static fn () => null;
    }

    protected function getTotalUniqueTranslationKeyCount(): int
    {
        $count = 0;

        foreach ($this->drivers() as $driverClass => $driverParams) {
            /** @var Driverable $driver */
            $driver = app($driverClass, $driverParams);

            $translationSets = $driver->getTranslationSets(
                $this->project(),
                $this->branch(),
                $this->passesFilterFactory(),
            );

            $analyzed = ProjectAnalysis::fromTranslationSets($translationSets);

            $count = $count + $analyzed->summary->unique_translation_key_count;
        }

        return $count;
    }
}
