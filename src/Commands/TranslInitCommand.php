<?php

declare(strict_types=1);

namespace Transl\Commands;

use Transl\Facades\Transl;
use Illuminate\Console\Command;
use Transl\Support\Push\PushBatch;
use Transl\Commands\Concerns\UsesBranch;
use Transl\Commands\Concerns\UsesConfig;
use Transl\Commands\Concerns\UsesProject;
use Transl\Commands\Concerns\NeedsHelpers;
use Transl\Commands\Concerns\UsesProgressBar;
use Transl\Actions\Commands\InitCommandAction;
use Symfony\Component\Console\Helper\ProgressBar;
use Transl\Commands\Concerns\CountsTranslationSet;
use Transl\Commands\Concerns\FiltersTranslationSet;
use Transl\Commands\Concerns\OutputsRecapToConsole;

class TranslInitCommand extends Command
{
    use UsesConfig;
    use UsesProject;
    use UsesBranch;
    use FiltersTranslationSet;
    use CountsTranslationSet;
    use OutputsRecapToConsole;
    use NeedsHelpers;
    use UsesProgressBar;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        transl:init

        {--project=            : The configured project to target (should be the `auth_key` or the `name` of a project defined in `config(transl.projects)`)}
        {--branch=             : The branch on the defined project to target (if the value provided does not exist, it will be created on Transl.me)}

        {--only-locales=*      : Will push only translation lines from the specified locales (expects a comma separated list or providing this option multiple times)}
        {--only-groups=*       : Will push only translation lines from the specified groups (expects a comma separated list or providing this option multiple times)}
        {--only-namespaces=*   : Will push only translation lines from the specified namespaces (expects a comma separated list or providing this option multiple times)}

        {--except-locales=*    : Will push only translation lines NOT from the specified locales (expects a comma separated list or providing this option multiple times)}
        {--except-groups=*     : Will push only translation lines NOT from the specified groups (expects a comma separated list or providing this option multiple times)}
        {--except-namespaces=* : Will push only translation lines NOT from the specified namespaces (expects a comma separated list or providing this option multiple times)}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Initializes the defined project (pushes the initial translation lines) on Transl.me";

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
        $this->hydrateOnlyLocalesProperty($this->optionAsNullableArray('only-locales'));
        $this->hydrateOnlyGroupsProperty($this->optionAsNullableArray('only-groups'));
        $this->hydrateOnlyNamespacesProperty($this->optionAsNullableArray('only-namespaces'));
        $this->hydrateExceptLocalesProperty($this->optionAsNullableArray('except-locales'));
        $this->hydrateExceptGroupsProperty($this->optionAsNullableArray('except-groups'));
        $this->hydrateExceptNamespacesProperty($this->optionAsNullableArray('except-namespaces'));
    }

    /* Actions
    ------------------------------------------------*/

    protected function handler(ProgressBar $bar): void
    {
        $count = $this->count();

        $batch = PushBatch::new($count);

        $bar->setMaxSteps($count);

        $this
            ->actionCommand()
            ->acceptsLocales($this->onlyLocales)
            ->acceptsGroups($this->onlyGroups)
            ->acceptsNamespaces($this->onlyNamespaces)
            ->rejectsLocales($this->exceptLocales)
            ->rejectsGroups($this->exceptGroups)
            ->rejectsNamespaces($this->exceptNamespaces)
            ->onTranslationSetHandled(static fn () => $bar->advance())
            ->execute($this->project, $this->branch, $batch);
    }

    protected function handled(): int
    {
        $this->newLine(2);

        $this->components->info("The project `{$this->project->name}` was initialized successfully!");

        return self::SUCCESS;
    }

    protected function actionCommand(): InitCommandAction
    {
        return Transl::commands()->init();
    }

    /* Console outputs
    ------------------------------------------------*/

    protected function beforeRecap(): void
    {
        $this->components->info("Initializing:");
    }
}
