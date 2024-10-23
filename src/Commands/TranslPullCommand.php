<?php

declare(strict_types=1);

namespace Transl\Commands;

use Transl\Facades\Transl;
use Illuminate\Console\Command;
use Transl\Support\TranslationSet;
use Transl\Commands\Concerns\UsesBranch;
use Transl\Commands\Concerns\UsesConfig;
use Transl\Commands\Concerns\UsesProject;
use Transl\Commands\Concerns\NeedsHelpers;
use Transl\Support\TranslationLinesDiffing;
use Transl\Commands\Concerns\UsesProgressBar;
use Transl\Actions\Commands\PullCommandAction;
use Symfony\Component\Console\Helper\ProgressBar;
use Transl\Commands\Concerns\FiltersTranslationSet;
use Transl\Commands\Concerns\OutputsRecapToConsole;
use Transl\Config\Enums\BranchingConflictResolutionEnum;

class TranslPullCommand extends Command
{
    use UsesConfig;
    use UsesProject;
    use UsesBranch;
    use FiltersTranslationSet;
    use OutputsRecapToConsole;
    use NeedsHelpers;
    use UsesProgressBar;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        transl:pull

        {--project=            : The configured project to target (should be the `auth_key` or the `name` of a project defined in `config(transl.projects)`)}
        {--branch=             : The branch on the defined project to target (if the value provided does not exist, it will be created on Transl.me)}
        {--conflicts=          : The strategy to be used to resolve conflicts (see the enum `\Transl\Config\Enums\BranchingConflictResolutionEnum` for allowed values)}

        {--only-locales=*      : Will pull only translation lines from the specified locales (expects a comma separated list or providing this option multiple times)}
        {--only-groups=*       : Will pull only translation lines from the specified groups (expects a comma separated list or providing this option multiple times)}
        {--only-namespaces=*   : Will pull only translation lines from the specified namespaces (expects a comma separated list or providing this option multiple times)}

        {--except-locales=*    : Will pull only translation lines NOT from the specified locales (expects a comma separated list or providing this option multiple times)}
        {--except-groups=*     : Will pull only translation lines NOT from the specified groups (expects a comma separated list or providing this option multiple times)}
        {--except-namespaces=* : Will pull only translation lines NOT from the specified namespaces (expects a comma separated list or providing this option multiple times)}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Retrieves and stores the defined project's translation lines from Transl.me";

    /**
     * The strategy to be used to resolve conflicts.
     */
    protected BranchingConflictResolutionEnum $conflicts;

    /**
     * Will contain translation lines that are in conflict.
     */
    protected array $conflictsData = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->hydrateProperties();

        $this->outputRecap();

        $this->newLine();

        $bar = $this->createProgressBar();

        $bar->start();

        $this->handler($bar);

        $bar->finish();

        return $this->handled();
    }

    /* Hydration
    ------------------------------------------------*/

    protected function hydrateProperties(): void
    {
        $this->hydrateConfigProperty();
        $this->hydrateProjectProperty($this->optionAsNullableString('project'));
        $this->hydrateBranchProperty($this->optionAsNullableString('branch'));
        $this->hydrateConflictsProperty($this->optionAsNullableString('conflicts'));
        $this->hydrateOnlyLocalesProperty($this->optionAsNullableArray('only-locales'));
        $this->hydrateOnlyGroupsProperty($this->optionAsNullableArray('only-groups'));
        $this->hydrateOnlyNamespacesProperty($this->optionAsNullableArray('only-namespaces'));
        $this->hydrateExceptLocalesProperty($this->optionAsNullableArray('except-locales'));
        $this->hydrateExceptGroupsProperty($this->optionAsNullableArray('except-groups'));
        $this->hydrateExceptNamespacesProperty($this->optionAsNullableArray('except-namespaces'));
    }

    protected function hydrateConflictsProperty(?string $value): void
    {
        $value = $value ? trim($value) : null;

        if ($value) {
            $value = BranchingConflictResolutionEnum::from($value);
        }

        if (!$value) {
            $value = $this->project->options->branching->conflict_resolution;
        }

        $this->conflicts = $value;
    }

    /* Actions
    ------------------------------------------------*/

    protected function handler(ProgressBar $bar): void
    {
        $this
            ->actionCommand()
            ->acceptsLocales($this->onlyLocales)
            ->acceptsGroups($this->onlyGroups)
            ->acceptsNamespaces($this->onlyNamespaces)
            ->rejectsLocales($this->exceptLocales)
            ->rejectsGroups($this->exceptGroups)
            ->rejectsNamespaces($this->exceptNamespaces)
            ->silenceConflictExceptions()
            ->onTranslationSetHandled(static fn () => $bar->advance())
            ->onIncomingTranslationSetConflicts(
                fn (TranslationSet $set, TranslationLinesDiffing $diff) => $this->collectConflictsData($set, $diff),
            )
            ->execute($this->project, $this->branch, $this->conflicts);
    }

    protected function handled(): int
    {
        $this->newLine(2);

        $this->outputConflictsAwareSummaryText();
        $this->outputConflictsData();

        if (!empty($this->conflictsData) && $this->conflicts === BranchingConflictResolutionEnum::THROW) {
            return self::FAILURE;
        }

        if (!empty($this->conflictsData) && $this->conflicts === BranchingConflictResolutionEnum::MERGE_BUT_THROW) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function actionCommand(): PullCommandAction
    {
        return Transl::commands()->pull();
    }

    protected function collectConflictsData(TranslationSet $incoming, TranslationLinesDiffing $diff): void
    {
        if ($diff->conflictingLines()->isEmpty()) {
            return;
        }

        $updatedLineKeys = array_keys($diff->updatedLines()->toRawTranslationLines());
        $addedLineKeys = array_keys($diff->addedLines()->toRawTranslationLines());
        $removedLineKeys = array_keys($diff->removedLines()->toRawTranslationLines());
        $conflictingLineKeys = array_keys($diff->conflictingLines()->toRawTranslationLines());

        $dataKey = $incoming->translationKey() . ' · ' . $incoming->locale; // •·
        $currentData = $this->conflictsData[$dataKey] ?? [];
        $data = [
            'updated' => array_intersect_key($updatedLineKeys, $conflictingLineKeys),
            'added' => array_intersect_key($addedLineKeys, $conflictingLineKeys),
            'removed' => array_intersect_key($removedLineKeys, $conflictingLineKeys),
        ];

        sort($data['updated']);
        sort($data['added']);
        sort($data['removed']);

        $this->conflictsData = [
            ...$this->conflictsData,
            $dataKey => [
                ...$currentData,
                'updated' => [
                    ...($currentData['updated'] ?? []),
                    ...$data['updated'],
                ],
                'added' => [
                    ...($currentData['added'] ?? []),
                    ...$data['added'],
                ],
                'removed' => [
                    ...($currentData['removed'] ?? []),
                    ...$data['removed'],
                ],
            ],
        ];
    }

    /* Console outputs
    ------------------------------------------------*/

    protected function beforeRecap(): void
    {
        $this->components->info("Pulling translation lines for:");
    }

    protected function recapExtraData(): array
    {
        return ['Conflict resolution' => $this->conflicts->value];
    }

    protected function outputConflictsAwareSummaryText(): void
    {
        if (empty($this->conflictsData)) {
            $this->components->info("The translation lines of the project `{$this->project->name}` were pulled successfully!");
        }

        if (!empty($this->conflictsData) && $this->conflicts === BranchingConflictResolutionEnum::IGNORE) {
            $this->components->warn("The translation lines of the project `{$this->project->name}` were pulled with conflicts:");
        }

        if (!empty($this->conflictsData) && $this->conflicts === BranchingConflictResolutionEnum::MERGE_AND_IGNORE) {
            $this->components->warn("The translation lines of the project `{$this->project->name}` were pulled with conflicts:");
        }

        if (!empty($this->conflictsData) && $this->conflicts === BranchingConflictResolutionEnum::THROW) {
            $this->components->error("The translation lines of the project `{$this->project->name}` were pulled with conflicts:");
        }

        if (!empty($this->conflictsData) && $this->conflicts === BranchingConflictResolutionEnum::MERGE_BUT_THROW) {
            $this->components->error("The translation lines of the project `{$this->project->name}` were pulled with conflicts:");
        }
    }

    protected function outputConflictsData(): void
    {
        if (empty($this->conflictsData)) {
            return;
        }

        collect($this->conflictsData)
            ->sortBy(static function (array $data, string $section): int {
                if ($section === '') {
                    return PHP_INT_MAX;
                }

                return count($data['added']) + count($data['updated']) + count($data['removed']);
            })
            ->each(function (array $data, string $section): void {
                $this->newLine();

                if ($section === '') {
                    $section = 'UNGROUPED';
                }

                $conflictsCount = count($data['added']) + count($data['updated']) + count($data['removed']);
                $conflictsText = $conflictsCount > 1 ? 'conflicts' : 'conflict';

                $this->components->twoColumnDetail("  <fg=cyan;options=bold>{$section} ({$conflictsCount} {$conflictsText})</>");

                foreach ($data['added'] as $key) {
                    $this->components->twoColumnDetail($key, '  <fg=green;options=bold>added</>');
                }

                foreach ($data['updated'] as $key) {
                    $this->components->twoColumnDetail($key, '  <fg=blue;options=bold>updated</>');
                }

                foreach ($data['removed'] as $key) {
                    $this->components->twoColumnDetail($key, '  <fg=red;options=bold>removed</>');
                }
            });
    }
}
