<?php

declare(strict_types=1);

namespace Transl\Actions\Commands;

use Closure;
use Generator;
use Transl\Facades\Transl;
use Transl\Support\Branch;
use Transl\Support\TranslationSet;
use Transl\Support\TranslationLine;
use Transl\Config\ProjectConfiguration;
use Transl\Support\Contracts\Driverable;
use Transl\Support\TranslationLinesDiffing;
use Transl\Api\Resources\Translation\Set\Set;
use Transl\Support\TranslationLineCollection;
use Transl\Api\Resources\Translation\Line\Line;
use Transl\Api\Responses\Commands\PullResponse;
use Transl\Actions\Commands\AbstractCommandAction;
use Transl\Config\Enums\BranchingConflictResolutionEnum;
use Transl\Exceptions\Branching\CouldNotResolveConflictWhilePulling;

class PullCommandAction extends AbstractCommandAction
{
    /**
     * The strategy to be used to resolve conflicts.
     */
    protected BranchingConflictResolutionEnum $conflictResolution;

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
    ): void {
        $this->usingProject($project);
        $this->usingBranch($branch);
        $this->usingConflictResolution($conflictResolution ?: $this->defaultConflictResolution());

        foreach ($this->pullFromTransl() as $incoming) {
            if (!$this->passesFilter($incoming->locale, $incoming->group, $incoming->namespace)) {
                $this->invokeTranslationSetSkippedCallback($incoming);

                continue;
            }

            foreach ($this->drivers() as $driverClass => $driverParams) {
                /** @var Driverable $driver */
                $driver = app($driverClass, $driverParams);

                $current = $this->getCurrentTranslationSet($driver, $incoming);
                $tracked = $this->getTrackedTranslationSet($driver, $incoming);

                $diff = $this->getIncomingTranslationSetDiff($tracked, $current, $incoming);

                if (!$tracked) {
                    $this->savePreviouslyUntrackedTranslationSet($driver, $incoming, $diff);

                    continue;
                }

                if ($this->conflictResolution() === BranchingConflictResolutionEnum::ACCEPT_INCOMING) {
                    $this->saveAcceptingIncomingTranslationSet($driver, $incoming, $diff);

                    continue;
                }

                if ($this->conflictResolution() === BranchingConflictResolutionEnum::ACCEPT_CURRENT) {
                    $this->saveAcceptingCurrentTranslationSet($driver, $incoming, $diff);

                    continue;
                }

                $hasConflics = $diff->conflictingLines()->isNotEmpty();

                if ($hasConflics) {
                    $this->invokeIncomingTranslationSetConflictsHandler($incoming, $diff);
                }

                if ($hasConflics && ($this->conflictResolution() === BranchingConflictResolutionEnum::THROW)) {
                    if ($this->shouldSilenceConflictExceptions) {
                        continue;
                    }

                    throw CouldNotResolveConflictWhilePulling::make($this->project(), $this->branch(), $incoming);
                }

                if ($hasConflics && ($this->conflictResolution() === BranchingConflictResolutionEnum::IGNORE)) {
                    continue;
                }

                $this->saveConsideringTranslationSetConflictingLines($driver, $incoming, $diff);

                if ($hasConflics && ($this->conflictResolution() === BranchingConflictResolutionEnum::MERGE_BUT_THROW)) {
                    if ($this->shouldSilenceConflictExceptions) {
                        continue;
                    }

                    throw CouldNotResolveConflictWhilePulling::make($this->project(), $this->branch(), $incoming);
                }
            }

            $this->invokeTranslationSetHandledCallback($incoming);
        }
    }

    /* Hydration
    ------------------------------------------------*/

    public function silenceConflictExceptions(): static
    {
        $this->shouldSilenceConflictExceptions = true;

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

    /* Accessors
    ------------------------------------------------*/

    public function conflictResolution(): BranchingConflictResolutionEnum
    {
        return $this->conflictResolution;
    }

    /* Hydration (bis)
    ------------------------------------------------*/

    protected function usingConflictResolution(BranchingConflictResolutionEnum $value): static
    {
        $this->conflictResolution = $value;

        return $this;
    }

    /* Actions
    ------------------------------------------------*/

    /**
     * @return Generator<int, TranslationSet>
     */
    protected function pullFromTransl(): Generator
    {
        /** @var PullResponse|null $response */
        $response = null;

        $filters = array_filter([
            'only_locales' => implode(',', $this->acceptedLocales()),
            'only_groups' => implode(',', $this->acceptedGroups()),
            'only_namespaces' => implode(',', $this->acceptedNamespaces()),
            'except_locales' => implode(',', $this->rejectedLocales()),
            'except_groups' => implode(',', $this->rejectedGroups()),
            'except_namespaces' => implode(',', $this->rejectedNamespaces()),
        ]);

        do {
            $response = Transl::api()->commands()->pull(
                $this->project(),
                $this->branch(),
                [
                    ...$filters,
                    'cursor' => $response?->pagination->next_cursor,
                ],
            );

            /** @var Set $set */
            foreach ($response->data as $set) {
                yield TranslationSet::new(
                    locale: $set->attributes->locale,
                    group: $set->attributes->group,
                    namespace: $set->attributes->namespace,
                    lines: TranslationLineCollection::make(
                        array_map(static function (Line $line): TranslationLine {
                            return TranslationLine::make(
                                key: $line->attributes->key,
                                value: $line->attributes->value,
                                meta: $line->meta,
                            );
                        }, $set->relations->lines),
                    ),
                    meta: $set->meta,
                );
            }
        } while ($response->pagination->has_more_pages);
    }

    protected function getCurrentTranslationSet(Driverable $driver, TranslationSet $incoming): TranslationSet
    {
        return $driver->getTranslationSet(
            $this->project(),
            $this->branch(),
            $incoming->locale,
            $incoming->group,
            $incoming->namespace,
            null,
        );
    }

    protected function getTrackedTranslationSet(Driverable $driver, TranslationSet $incoming): ?TranslationSet
    {
        return $driver->getTrackedTranslationSet($this->project(), $this->branch(), $incoming);
    }

    protected function getIncomingTranslationSetDiff(?TranslationSet $tracked, TranslationSet $current, TranslationSet $incoming): TranslationLinesDiffing
    {
        return $incoming->diff(
            trackedLines: $tracked ?: TranslationLineCollection::make(),
            currentLines: $current,
        );
    }

    protected function savePreviouslyUntrackedTranslationSet(Driverable $driver, TranslationSet $incoming, TranslationLinesDiffing $diff): void
    {
        $this->saveConsideringTranslationSetConflictingLines($driver, $incoming, $diff);
    }

    protected function saveAcceptingIncomingTranslationSet(Driverable $driver, TranslationSet $incoming, TranslationLinesDiffing $diff): void
    {
        $this->save(
            driver: $driver,
            incoming: $incoming,
            saveableLines: $diff->favorIncomingLines(),
        );
    }

    protected function saveAcceptingCurrentTranslationSet(Driverable $driver, TranslationSet $incoming, TranslationLinesDiffing $diff): void
    {
        $this->save(
            driver: $driver,
            incoming: $incoming,
            saveableLines: $diff->favorCurrentLines(),
        );
    }

    protected function saveConsideringTranslationSetConflictingLines(Driverable $driver, TranslationSet $incoming, TranslationLinesDiffing $diff): void
    {
        $this->save(
            driver: $driver,
            incoming: $incoming,
            saveableLines: $diff->mergeableLines(),
        );
    }

    protected function save(Driverable $driver, TranslationSet $incoming, TranslationLineCollection $saveableLines): void
    {
        $saveable = TranslationSet::new(
            $incoming->locale,
            $incoming->group,
            $incoming->namespace,
            $saveableLines,
            $incoming->meta,
        );

        $driver->saveTranslationSet($this->project(), $this->branch(), $saveable);
    }

    protected function invokeIncomingTranslationSetConflictsHandler(TranslationSet $incoming, TranslationLinesDiffing $diff): void
    {
        if (!$this->incomingTranslationSetConflictsHandler) {
            return;
        }

        ($this->incomingTranslationSetConflictsHandler)($incoming, $diff);
    }

    /* Helpers
    ------------------------------------------------*/

    protected function defaultConflictResolution(): BranchingConflictResolutionEnum
    {
        return $this->project()->options->branching->conflict_resolution;
    }
}
