<?php

declare(strict_types=1);

namespace Transl\Actions\Commands;

use Closure;
use Transl\Support\Branch;
use Transl\Support\Push\PushBatch;
use Transl\Support\TranslationSet;
use Transl\Config\ProjectConfiguration;
use Transl\Support\TranslationLinesDiffing;
use Transl\Actions\Commands\PullCommandAction;
use Transl\Actions\Commands\PushCommandAction;
use Transl\Actions\Commands\AbstractCommandAction;
use Transl\Config\Enums\BranchingConflictResolutionEnum;

class SynchCommandAction extends AbstractCommandAction
{
    /**
     * A callback invoked when the target TranslationSet being pulled has been skipped.
     */
    protected ?Closure $pulledTranslationSetSkippedCallback = null;

    /**
     * A callback invoked once the target TranslationSet being pulled has been handled.
     */
    protected ?Closure $pulledTranslationSetHandledCallback = null;

    /**
     * A callback invoked when the target TranslationSet being pushed has been skipped.
     */
    protected ?Closure $pushedTranslationSetSkippedCallback = null;

    /**
     * A callback invoked once the target TranslationSet being pushed has been handled.
     */
    protected ?Closure $pushedTranslationSetHandledCallback = null;

    /**
     * Force silencing conflict exceptions regardless of the
     * defined conflict resolution strategy.
     */
    protected bool $shouldSilenceConflictExceptions = false;

    /**
     * A callback invoked once an incoming TranslationSet conflict has been detected.
     */
    protected ?Closure $incomingTranslationSetConflictsHandler = null;

    /**
     * Execute the action.
     */
    public function execute(
        ProjectConfiguration $project,
        Branch $branch,
        ?BranchingConflictResolutionEnum $conflictResolution = null,
        ?PushBatch $batch = null,
    ): void {
        $this->usingProject($project);
        $this->usingBranch($branch);

        $this->pull($project, $branch, $conflictResolution);
        $this->push($project, $branch, $batch);
    }

    /* Hydration
    ------------------------------------------------*/

    /**
     * @param Closure(TranslationSet): void $callback
     */
    public function onPulledTranslationSetSkipped(Closure $callback): static
    {
        $this->pulledTranslationSetSkippedCallback = $callback;

        return $this;
    }

    /**
     * @param Closure(TranslationSet): void $callback
     */
    public function onPulledTranslationSetHandled(Closure $callback): static
    {
        $this->pulledTranslationSetHandledCallback = $callback;

        return $this;
    }

    /**
     * @param Closure(TranslationSet): void $callback
     */
    public function onPushedTranslationSetSkipped(Closure $callback): static
    {
        $this->pushedTranslationSetSkippedCallback = $callback;

        return $this;
    }

    /**
     * @param Closure(TranslationSet): void $callback
     */
    public function onPushedTranslationSetHandled(Closure $callback): static
    {
        $this->pushedTranslationSetHandledCallback = $callback;

        return $this;
    }

    /**
     * @param Closure(TranslationSet, TranslationLinesDiffing ): void $callback
     */
    public function onIncomingTranslationSetConflicts(Closure $callback): static
    {
        $this->incomingTranslationSetConflictsHandler = $callback;

        return $this;
    }

    public function silenceConflictExceptions(): static
    {
        $this->shouldSilenceConflictExceptions = true;

        return $this;
    }

    /* Actions
    ------------------------------------------------*/

    protected function pullCommandAction(): PullCommandAction
    {
        return app(PullCommandAction::class);
    }

    protected function pushCommandAction(): PushCommandAction
    {
        return app(PushCommandAction::class);
    }

    protected function pull(
        ProjectConfiguration $project,
        Branch $branch,
        ?BranchingConflictResolutionEnum $conflictResolution = null,
    ): void {
        $action = $this
            ->pullCommandAction()
            ->acceptsLocales($this->onlyLocales)
            ->acceptsGroups($this->onlyGroups)
            ->acceptsNamespaces($this->onlyNamespaces)
            ->rejectsLocales($this->exceptLocales)
            ->rejectsGroups($this->exceptGroups)
            ->rejectsNamespaces($this->exceptNamespaces);

        if ($this->shouldSilenceConflictExceptions) {
            $action->silenceConflictExceptions();
        }

        $action
            ->onTranslationSetSkipped($this->pulledTranslationSetSkippedCallback ?: $this->translationSetSkippedCallback ?: $this->noop())
            ->onTranslationSetHandled($this->pulledTranslationSetHandledCallback ?: $this->translationSetHandledCallback ?: $this->noop())
            ->onIncomingTranslationSetConflicts($this->incomingTranslationSetConflictsHandler ?: $this->noop())
            ->execute($project, $branch, $conflictResolution);
    }

    protected function push(ProjectConfiguration $project, Branch $branch, ?PushBatch $batch = null): void
    {
        $this
            ->pushCommandAction()
            ->acceptsLocales($this->onlyLocales)
            ->acceptsGroups($this->onlyGroups)
            ->acceptsNamespaces($this->onlyNamespaces)
            ->rejectsLocales($this->exceptLocales)
            ->rejectsGroups($this->exceptGroups)
            ->rejectsNamespaces($this->exceptNamespaces)
            ->onTranslationSetSkipped($this->pushedTranslationSetSkippedCallback ?: $this->translationSetSkippedCallback ?: $this->noop())
            ->onTranslationSetHandled($this->pushedTranslationSetHandledCallback ?: $this->translationSetHandledCallback ?: $this->noop())
            ->execute($project, $branch, $batch);
    }

    protected function noop(): Closure
    {
        return static fn () => null;
    }
}
