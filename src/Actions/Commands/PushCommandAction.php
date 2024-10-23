<?php

declare(strict_types=1);

namespace Transl\Actions\Commands;

use Transl\Facades\Transl;
use Transl\Support\Branch;
use Transl\Support\Push\PushBatch;
use Transl\Support\Push\PushChunk;
use Transl\Support\TranslationSet;
use Transl\Config\ProjectConfiguration;
use Transl\Support\Contracts\Driverable;
use Transl\Actions\Commands\AbstractCommandAction;
use Transl\Actions\Commands\CountPushableTranslationSetsActions;

class PushCommandAction extends AbstractCommandAction
{
    /**
     * Will push and save the translation lines with
     * the given message.
     */
    protected ?string $message = null;

    /**
     * Set's a message to be used for the pushed translation
     * lines once they are saved on Transl.
     */
    public function withMessage(?string $value): static
    {
        $this->message = $value;

        return $this;
    }

    /**
     * Execute the action.
     */
    public function execute(ProjectConfiguration $project, Branch $branch, ?PushBatch $batch = null, array $meta = []): void
    {
        $this->usingProject($project);
        $this->usingBranch($branch);

        if (!$batch) {
            $batch = PushBatch::new($this->count());
        }

        $filter = $this->passesFilterFactory();
        $onSkipped = $this->translationSetSkippedCallback
            ? fn (TranslationSet $translationSet) => $this->invokeTranslationSetSkippedCallback($translationSet)
            : null;

        foreach ($this->drivers() as $driverClass => $driverParams) {
            /** @var Driverable $driver */
            $driver = app($driverClass, $driverParams);

            $translationSets = $driver->getTranslationSets(
                $this->project(),
                $this->branch(),
                $filter,
                $onSkipped,
            );

            $push = fn () => $this->push($batch, $meta, $driver);

            foreach ($translationSets as $translationSet) {
                $batch->addUntilPoolFull($translationSet, $push);

                if (!$translationSet->group) {
                    $batch->ensurePoolDrained($push);
                }
            }

            $batch->ensurePoolDrained($push);
        }

        if (!$batch->totalPushed()) {
            return;
        }

        $this->markPushAsEndedOnTransl($batch);
    }

    /* Actions
    ------------------------------------------------*/

    protected function count(): int
    {
        return app(CountPushableTranslationSetsActions::class)
            ->acceptsLocales($this->onlyLocales)
            ->acceptsGroups($this->onlyGroups)
            ->acceptsNamespaces($this->onlyNamespaces)
            ->rejectsLocales($this->exceptLocales)
            ->rejectsGroups($this->exceptGroups)
            ->rejectsNamespaces($this->exceptNamespaces)
            ->execute($this->project(), $this->branch());
    }

    protected function push(PushBatch $batch, array $meta, Driverable $driver): void
    {
        $this->pushToTransl($batch, $meta, function (PushChunk $chunk) use ($driver): void {
            $this->savePushed($driver, $chunk);
        });
    }

    protected function savePushed(Driverable $driver, PushChunk $chunk): void
    {
        foreach ($chunk->translationSets() as $translationSet) {
            $driver->saveTrackedTranslationSet($this->project(), $this->branch(), $translationSet);

            $this->invokeTranslationSetHandledCallback($translationSet);
        }
    }

    /**
     * @param callable(PushChunk $chunk): void $onPushed
     */
    protected function pushToTransl(PushBatch $batch, array $meta, callable $onPushed): void
    {
        Transl::api()->commands()->push(
            $this->project(),
            $this->branch(),
            $batch,
            $onPushed,
            $meta,
        );
    }

    protected function markPushAsEndedOnTransl(PushBatch $batch): void
    {
        Transl::api()->commands()->pushEnd(
            $this->project(),
            $this->branch(),
            $batch,
            [
                'message' => $this->message,
            ],
        );
    }
}
