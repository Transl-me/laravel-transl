<?php

declare(strict_types=1);

namespace Transl\Commands;

use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use Transl\Commands\TranslPullCommand;
use Transl\Commands\TranslPushCommand;

class TranslSynchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        transl:synch

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
    protected $description = "Pulls then pushes the defined project's translation lines to Transl.me";

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $options = $this->providedOptions();

        $pulled = $this->call(TranslPullCommand::class, $options);

        $this->newLine(3);

        if ($pulled !== self::SUCCESS) {
            $this->outputFailureMessage();

            return self::FAILURE;
        }

        $pushed = $this->call(TranslPushCommand::class, Arr::except($options, ['--conflicts']));

        $this->newLine(3);

        if ($pushed !== self::SUCCESS) {
            $this->outputFailureMessage();

            return self::FAILURE;
        }

        $this->components->info('Translation lines synchronized successfully!');

        return self::SUCCESS;
    }

    protected function providedOptions(): array
    {
        return collect($this->options())->reduce(static function (array $acc, mixed $value, string $key) {
            $acc["--{$key}"] = $value;

            return $acc;
        }, []);
    }

    protected function outputFailureMessage(): void
    {
        $this->components->error('Failed synchronizing the translation lines.');
    }
}
