<?php

declare(strict_types=1);

namespace Transl\Actions\LocalFilesDriver;

use Transl\Support\Helper;
use Illuminate\Support\Arr;
use Transl\Support\TranslationSet;
use Transl\Support\LocaleFilesystem\LangFilePath;
use Transl\Actions\LocalFilesDriver\AbstractLocalFilesDriverAction;
use Transl\Exceptions\LocalFilesDriver\CouldNotDetermineTranslationFileRelativePathFromTranslationSet;

class SaveTranslationSetToLocalFilesAction extends AbstractLocalFilesDriverAction
{
    protected TranslationSet $translationSet;

    public function usingTranslationSet(TranslationSet $translationSet): static
    {
        $this->translationSet = $translationSet;

        return $this;
    }

    /**
     * Execute the action.
     */
    public function execute(): void
    {
        $translationFile = LangFilePath::new(
            $this->determineTranslationFileRoot(),
            $this->determineTranslationFileRelativePath(),
        );

        $this->writeToTranslationFile($translationFile);
    }

    protected function defaultTranslationFileRoot(): string
    {
        return $this->driver->defaultLanguageDirectories($this->project, $this->branch)[0] ?? lang_path();
    }

    protected function determineTranslationFileRoot(): string
    {
        $default = $this->defaultTranslationFileRoot();

        $root = str_replace('\\', '/', $default);

        $languageDirectories = collect($this->languageDirectories)
            ->map(static fn (string $languageDirectory): string => str_replace('\\', '/', $languageDirectory))
            ->filter(static fn (string $languageDirectory): bool => !str_contains($languageDirectory, 'vendor/'));

        $languageDirectory = $languageDirectories
            ->first(static fn (string $languageDirectory): bool => $root === $languageDirectory);

        if (!$languageDirectory) {
            $languageDirectory = $languageDirectories->first() ?: $default;
        }

        return $languageDirectory;
    }

    protected function determineTranslationFileRelativePath(): string
    {
        $set = $this->translationSet;

        if ($set->namespace && $set->group) {
            return "vendor/{$set->namespace}/{$set->locale}/{$set->group}.php";
        }

        /**
         * This is not a case, having packages provide JSON
         * translation files, handled by Laravel's FileLoader.
         * Therefore, we shouldn't encounter nor handle it.
         *
         * @see vendor/laravel/framework/src/Illuminate/Translation/FileLoader.php@load
         */
        // if ($set->namespace && !$set->group) {
        //     return "vendor/{$set->namespace}/{$set->locale}.json";
        // }

        if (!$set->namespace && $set->group) {
            return "{$set->locale}/{$set->group}.php";
        }

        if (!$set->namespace && !$set->group) {
            return "{$set->locale}.json";
        }

        throw CouldNotDetermineTranslationFileRelativePathFromTranslationSet::make($set, $this->driver::class);
    }

    protected function writeToTranslationFile(LangFilePath $translationFile): void
    {
        $translationFile->isJson()
            ? $this->writeToJsonTranslationFile($translationFile)
            : $this->writeToPhpTranslationFile($translationFile);
    }

    protected function writeToJsonTranslationFile(LangFilePath $translationFile): void
    {
        $content = $this->translationSet->lines->toRawTranslationLinesWithPotentiallyOriginalValues();
        $content = Helper::jsonEncode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $this->driver->filesystem()->ensureDirectoryExists(dirname($translationFile->fullPath()));
        $this->driver->filesystem()->put($translationFile->fullPath(), $content);
    }

    protected function writeToPhpTranslationFile(LangFilePath $translationFile): void
    {
        $content = $this->translationSet->lines->toRawTranslationLinesWithPotentiallyOriginalValues();
        $content = Arr::undot($content);

        $content = <<<PHP
        <?php

        return [
            {$this->phpArrayToString($content)}
        ];

        PHP;

        $this->driver->filesystem()->ensureDirectoryExists(dirname($translationFile->fullPath()));
        $this->driver->filesystem()->put($translationFile->fullPath(), $content);
    }

    protected function phpArrayToString(array $items, int $depth = 1, int $indentSpaces = 4): string
    {
        $indentation = str_repeat(' ', $indentSpaces * $depth);
        $indentationMinus1 = str_repeat(' ', $indentSpaces * ($depth - 1));

        $n = PHP_EOL;

        $content = $depth === 1 ? $n : "[{$n}";

        $asList = Arr::isList($items);

        foreach ($items as $key => $value) {
            $key = $this->varExport($key);

            if (!is_array($value)) {
                $value = $this->varExport($value);
            }

            if (is_array($value)) {
                $value = empty($value) ? '[]' : $this->phpArrayToString($value, $depth + 1, $indentSpaces);
            }

            if ($asList) {
                $content .= "{$indentation}{$value},{$n}";
            } else {
                $content .= "{$indentation}{$key} => {$value},{$n}";
            }
        }

        $content .= $depth === 1 ? '' : "{$indentationMinus1}]";

        return trim($content);
    }

    protected function varExport(mixed $value): string
    {
        return $value === null ? 'null' : var_export($value, true);
    }
}
