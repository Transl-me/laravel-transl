<?php

declare(strict_types=1);

namespace Transl\Actions\LocalFilesDriver;

use Closure;
use Generator;
use Transl\Support\TranslationSet;
use Transl\Support\LocaleFilesystem\FilePath;
use Transl\Support\LocaleFilesystem\LangFilePath;
use Transl\Actions\LocalFilesDriver\AbstractLocalFilesDriverAction;
use Transl\Exceptions\LocalFilesDriver\FoundDisallowedProjectLocale;
use Transl\Exceptions\LocalFilesDriver\CouldNotOpenLanguageDirectory;

abstract class AbstractLocalFilesRetrievalAction extends AbstractLocalFilesDriverAction
{
    /**
     * @var (Closure(string $locale, ?string $group, ?string $namespace): bool)|null
     */
    protected ?Closure $filter = null;

    /**
     * @var (Closure(TranslationSet $translationSet): void)|null
     */
    protected ?Closure $onSkipped = null;

    /**
     * @param (Closure(string $locale, ?string $group, ?string $namespace): bool)|null $filter
     */
    public function usingFilter(?Closure $filter): static
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * @param (Closure(TranslationSet $translationSet): void)|null $onSkipped
     */
    public function onSkipped(?Closure $onSkipped): static
    {
        $this->onSkipped = $onSkipped;

        return $this;
    }

    /**
     * @return Generator<int, LangFilePath>
     */
    protected function recursivelyReadDirectory(FilePath $directory, ?FilePath $root = null): Generator
    {
        if (!$root) {
            $root = $directory;
        }

        $handle = $directory->exists() ? opendir($directory->fullPath()) : null;

        if (!$handle) {
            throw CouldNotOpenLanguageDirectory::make($directory, $this->driver::class);
        }

        while (($path = readdir($handle)) !== false) {
            if ($path === '.' || $path === '..') {
                continue;
            }

            $relativePath = $directory->append($path)->relativeFrom($root);
            $path = LangFilePath::new($root->fullPath(), $relativePath);

            if ($path->isDirectory()) {
                foreach ($this->recursivelyReadDirectory($path, $root) as $value) {
                    yield $value;
                }
            } else {
                yield $path;
            }
        }

        closedir($handle);
    }

    protected function shouldIgnoreTranslationFile(LangFilePath $translationFile, FilePath $languageDirectory): bool
    {
        if ($this->ignorePackageTranslations && $translationFile->isPackage()) {
            return true;
        }

        return $this->ignoreVendorTranslations && $translationFile->inVendor();
    }

    protected function allowsTranslationFileLocale(LangFilePath $translationFile, FilePath $languageDirectory): bool
    {
        $option = $this->project->options->locale;

        if (is_null($option->allowed)) {
            return true;
        }

        $locale = $this->getTranslationFileLocale($translationFile, $languageDirectory);

        if (in_array($locale, $option->allowed, true)) {
            return true;
        }

        if (!$option->throw_on_disallowed_locale) {
            return false;
        }

        throw FoundDisallowedProjectLocale::make($locale, $this->project);
    }

    protected function passesFilter(
        LangFilePath $translationFile,
        FilePath $languageDirectory,
    ): bool {
        $locale = $this->getTranslationFileLocale($translationFile, $languageDirectory);
        $group = $this->getTranslationFileGroup($translationFile, $languageDirectory);
        $namespace = $this->getTranslationFileNamespace($translationFile, $languageDirectory);

        if (!$this->filter) {
            return true;
        }

        return ($this->filter)($locale, $group, $namespace);
    }

    protected function getTranslationFileLocale(LangFilePath $translationFile, FilePath $languageDirectory): string
    {
        return $translationFile->guessLocale($languageDirectory);
    }

    protected function getTranslationFileGroup(LangFilePath $translationFile, FilePath $languageDirectory): ?string
    {
        return $translationFile->guessGroup($languageDirectory);
    }

    protected function getTranslationFileNamespace(LangFilePath $translationFile, FilePath $languageDirectory): ?string
    {
        return $translationFile->guessNamespace($languageDirectory);
    }

    protected function makeTranslationSetFromTranslationFile(
        LangFilePath $translationFile,
        FilePath $languageDirectory,
    ): TranslationSet {
        $locale = $this->getTranslationFileLocale($translationFile, $languageDirectory);
        $group = $this->getTranslationFileGroup($translationFile, $languageDirectory);
        $namespace = $this->getTranslationFileNamespace($translationFile, $languageDirectory);

        return $this->driver->getTranslationSet(
            project: $this->project,
            branch: $this->branch,
            locale: $locale,
            group: $group,
            namespace: $namespace,
            meta: null,
        );
    }

    protected function handleSkipped(LangFilePath $translationFile, FilePath $languageDirectory): void
    {
        if (!$this->onSkipped) {
            return;
        }

        $translationSet = $this->makeTranslationSetFromTranslationFile($translationFile, $languageDirectory);

        ($this->onSkipped)($translationSet);
    }
}
