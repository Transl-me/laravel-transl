<?php

declare(strict_types=1);

namespace Transl\Commands;

use NumberFormatter;
use Illuminate\Support\Number;
use Illuminate\Console\Command;
use Transl\Commands\Concerns\UsesBranch;
use Transl\Commands\Concerns\UsesConfig;
use Transl\Support\Contracts\Driverable;
use Transl\Commands\Concerns\UsesProject;
use Transl\Commands\Concerns\NeedsHelpers;
use Transl\Support\Analysis\ProjectAnalysis;
use Transl\Commands\Concerns\OutputsRecapToConsole;
use Transl\Config\ProjectConfigurationDriverCollection;

class TranslAnalyseCommand extends Command
{
    use UsesConfig;
    use UsesProject;
    use UsesBranch;
    use OutputsRecapToConsole;
    use NeedsHelpers;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        transl:analyse

        {--project= : The configured project to target (should be the `auth_key` or the `name` of a project defined in `config(transl.projects)`)}
        {--branch=  : The branch on the defined project to target}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Analyses the defined project's translation lines";

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->hydrateProperties();

        $this->outputRecap();

        $this->handler();

        return $this->handled();
    }

    /* Hydration
    ------------------------------------------------*/

    protected function hydrateProperties(): void
    {
        $this->hydrateConfigProperty();
        $this->hydrateProjectProperty($this->optionAsNullableString('project'));
        $this->hydrateBranchProperty($this->optionAsNullableString('branch'));
    }

    /* Actions
    ------------------------------------------------*/

    protected function drivers(): ProjectConfigurationDriverCollection
    {
        return $this->project->drivers;
    }

    protected function handler(): void
    {
        foreach ($this->drivers() as $driverClass => $driverParams) {
            /** @var Driverable $driver */
            $driver = app($driverClass, $driverParams);

            $this->newLine();

            $this->outputAnalysis(
                $driver,
                ProjectAnalysis::fromTranslationSets($driver->getTranslationSets($this->project, $this->branch)),
            );
        }
    }

    protected function handled(): int
    {
        $this->newLine(2);

        return self::SUCCESS;
    }

    /* Console outputs
    ------------------------------------------------*/

    protected function outputAnalysis(Driverable $driver, ProjectAnalysis $analysis): void
    {
        $this->components->bulletList([
            'Using driver: ' . $driver::class,
        ]);

        $isVerbose = $this->getOutput()->isVerbose();
        $isVeryVerbose = $this->getOutput()->isVeryVerbose();

        $num = fn (int $value): string => $this->formatNumber($value);

        foreach ($analysis->locales as $locale => $analysedLocale) {
            $this->components->twoColumnDetail("• <fg=cyan;options=bold>{$locale}</>");

            // Output Translation sets
            if ($isVerbose) {
                $this->components->twoColumnDetail("·· <fg=yellow;options=bold>Translation sets</>");

                foreach ($analysedLocale->translation_sets as $translationSetKey => $analysedSet) {
                    $this->components->twoColumnDetail("···· {$translationSetKey}");

                    // Output Translation Lines
                    if ($isVeryVerbose) {
                        $this->components->twoColumnDetail("······ <fg=yellow;options=bold>Lines</>");

                        foreach ($analysedSet->lines as $translationLineKey => $analysedLine) {
                            $words = $num($analysedLine->word_count) . ($analysedLine->word_count > 1 ? ' words' : ' word');
                            $characters = $num($analysedLine->character_count) . ($analysedLine->character_count > 1 ? ' characters' : ' character');

                            $this->components->twoColumnDetail("········ <fg=gray;options=bold>{$translationLineKey}</>", "<fg=gray;options=bold>{$words} / {$characters}</>");
                        }

                        $this->components->twoColumnDetail("······ <fg=yellow;options=bold>Summary</>");
                        $this->components->twoColumnDetail("········ Translation lines", "{$num($analysedSet->summary->translation_line_count)}");
                        $this->components->twoColumnDetail("········ Translation line words", "{$num($analysedSet->summary->translation_line_word_count)}");
                        $this->components->twoColumnDetail("········ Translation line characters", "{$num($analysedSet->summary->translation_line_character_count)}");
                    } else {
                        $this->components->twoColumnDetail("······ <fg=gray;options=bold>Translation lines</>", "<fg=gray;options=bold>{$num($analysedSet->summary->translation_line_count)}</>");
                        $this->components->twoColumnDetail("······ <fg=gray;options=bold>Translation line words</>", "<fg=gray;options=bold>{$num($analysedSet->summary->translation_line_word_count)}</>");
                        $this->components->twoColumnDetail("······ <fg=gray;options=bold>Translation line characters</>", "<fg=gray;options=bold>{$num($analysedSet->summary->translation_line_character_count)}</>");
                    }
                }

                $this->components->twoColumnDetail("·· <fg=yellow;options=bold>`{$locale}` summary</>");
                $this->components->twoColumnDetail("···· Translation sets", "{$num($analysedLocale->summary->translation_set_count)}");
                $this->components->twoColumnDetail("···· Translation lines", "{$num($analysedLocale->summary->translation_line_count)}");
                $this->components->twoColumnDetail("···· Translation line words", "{$num($analysedLocale->summary->translation_line_word_count)}");
                $this->components->twoColumnDetail("···· Translation line characters", "{$num($analysedLocale->summary->translation_line_character_count)}");
            } else {
                $this->components->twoColumnDetail("·· Translation sets", "{$num($analysedLocale->summary->translation_set_count)}");
                $this->components->twoColumnDetail("·· Translation lines", "{$num($analysedLocale->summary->translation_line_count)}");
                $this->components->twoColumnDetail("·· Translation line words", "{$num($analysedLocale->summary->translation_line_word_count)}");
                $this->components->twoColumnDetail("·· Translation line characters", "{$num($analysedLocale->summary->translation_line_character_count)}");
            }
        }

        $this->components->twoColumnDetail("• <fg=yellow;options=bold>Project summary</>");
        $this->components->twoColumnDetail("·· Unique translation keys", "{$num($analysis->summary->unique_translation_key_count)}");
        $this->components->twoColumnDetail("·· Unique translation sets", "{$num($analysis->summary->unique_translation_set_count)}");
        $this->components->twoColumnDetail("·· Translation keys", "{$num($analysis->summary->translation_key_count)}");
        $this->components->twoColumnDetail("·· Translation sets", "{$num($analysis->summary->translation_set_count)}");
        $this->components->twoColumnDetail("·· Translation lines", "{$num($analysis->summary->translation_line_count)}");
        $this->components->twoColumnDetail("·· Translation line words", "{$num($analysis->summary->translation_line_word_count)}");
        $this->components->twoColumnDetail("·· Translation line characters", "{$num($analysis->summary->translation_line_character_count)}");
    }

    /* Helpers
    ------------------------------------------------*/

    protected function formatNumber(int $value): string
    {
        if (!extension_loaded('intl')) {
            return (string) $value;
        }

        $formatted = class_exists(Number::class)
            ? Number::format($value, locale: app()->getLocale())
            : (function () use ($value): string|bool {
                $formatter = new NumberFormatter(app()->getLocale(), NumberFormatter::DECIMAL);

                return $formatter->format($value);
            })();

        if (is_bool($formatted)) {
            return (string) $value;
        }

        return str($formatted)->ascii()->value();
    }
}
