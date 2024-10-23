<?php

declare(strict_types=1);

namespace Transl\Drivers;

use Transl\Support\Branch;
use Transl\Support\TranslationSet;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Transl\Config\ProjectConfiguration;
use Transl\Support\Contracts\Driverable;
use Illuminate\Contracts\Translation\Loader;
use Transl\Exceptions\LocalFilesDriver\UnsupportedTranslationLoader;
use Transl\Actions\LocalFilesDriver\SaveTranslationSetToLocalFilesAction;
use Transl\Actions\LocalFilesDriver\GetTranslationSetsFromLocalFilesAction;
use Transl\Actions\LocalFilesDriver\CountTranslationSetsFromLocalFilesAction;
use Transl\Actions\LocalFilesDriver\TranslationContentsToTranslationSetAction;
use Transl\Actions\LocalFilesDriver\GetTranslationContentsFromLocalFilesAction;
use Transl\Actions\LocalFilesDriver\SaveTrackedTranslationSetToLocalFilesAction;
use Transl\Actions\LocalFilesDriver\GetTrackedTranslationSetFromLocalFilesAction;

/**
 * A driver scanning local directories for translation files
 * based on Laravel's default behavior.
 */
class LocalFilesDriver implements Driverable
{
    public function __construct(
        /**
         * The language directories that should be scanned
         * for translation files. If `null`, will scan the
         * `lang_path()` directory.
         *
         * The directories provided should be those registered
         * by `\Illuminate\Translation\TranslationServiceProvider@registerLoader`.
         * Otherwise, although the translation files will be found, their retrieve
         * lines will be empty (an empty array).
         */
        protected readonly ?array $language_directories = null,

        /**
         * Whether to filter out package translation files from the
         * scannig process. Those are translation files tucked in
         * a "vendor" subdirectory.
         * Example: "/lang/vendor/a_package/en/validation.php".
         *
         * @see https://laravel.com/docs/10.x/packages#language-files
         * @see https://laravel.com/docs/10.x/localization#overriding-package-language-files
         */
        protected readonly bool $ignore_package_translations = false,

        /**
         * Whether to filter out translation files inside Composers's
         * "vendor" directory from the scannig process.
         *
         * Scanned vendor directories are thoses provided by the
         * `language_directories` properties above.
         */
        protected readonly bool $ignore_vendor_translations = false,

        /**
         * A class that gets the "raw" translation messages/contents array.
         * When using the default Laravel translation loader (`FileLoader`)
         * the returned array should be **mostly** equivalent to:
         * `__($namespace ? "{$namespace}::{$group}" : $group, [], $locale)`.
         */
        protected readonly string $get_translation_contents_action = GetTranslationContentsFromLocalFilesAction::class,

        /**
         * A class that converts "raw" translation messages/contents to
         * an instance of `\Transl\Support\TranslationSet`.
         */
        protected readonly string $translation_contents_to_translation_set_action = TranslationContentsToTranslationSetAction::class,

        /**
         * A class that counts the amount of `\Transl\Support\TranslationSet`s
         * to be retrieved.
         */
        protected readonly string $count_translation_sets_action = CountTranslationSetsFromLocalFilesAction::class,

        /**
         * A class that gets a collection of `\Transl\Support\TranslationSet`s.
         */
        protected readonly string $get_translation_sets_action = GetTranslationSetsFromLocalFilesAction::class,

        /**
         * A class that stores a `\Transl\Support\TranslationSet` in a way
         * that should be readable to a Laravel translation loader.
         */
        protected readonly string $save_translation_set_action = SaveTranslationSetToLocalFilesAction::class,

        /**
         * A class that gets a `\Transl\Support\TranslationSet` that has
         * previously been pushed to Transl, thus, "tracked" by Transl.
         * This translation set will be used in determining conflicts.
         */
        protected readonly string $get_tracked_translation_set_action = GetTrackedTranslationSetFromLocalFilesAction::class,

        /**
         * A class that stores a `\Transl\Support\TranslationSet` in a way
         * that would allow it to be reconstructed back (using `TranslationSet::from` for example).
         */
        protected readonly string $save_tracked_translation_set_action = SaveTrackedTranslationSetToLocalFilesAction::class,
    ) {
    }

    /* Translation contents
    ------------------------------------------------*/

    /**
     * Gets the "raw" translation messages/contents array.
     * When using the default Laravel translation loader (`FileLoader`)
     * the returned array should be **mostly** equivalent to:
     * `__($namespace ? "{$namespace}::{$group}" : $group, [], $locale)`.
     */
    public function getTranslationContents(
        ProjectConfiguration $project,
        Branch $branch,
        string $locale,
        ?string $group,
        ?string $namespace,
    ): array {
        return app($this->get_translation_contents_action)
            ->usingDriver($this)
            ->usingProject($project)
            ->usingBranch($branch)
            ->usingLanguageDirectories($this->languageDirectories($project, $branch))
            ->shouldIgnorePackageTranslations($this->ignore_package_translations)
            ->shouldIgnoreVendorTranslations($this->ignore_vendor_translations)
            ->execute($locale, $group, $namespace);
    }

    /**
     * Converts "raw" translation messages/contents to
     * an instance of `\Transl\Support\TranslationSet`.
     */
    public function translationContentsToTranslationSet(
        ProjectConfiguration $project,
        Branch $branch,
        array $contents,
        string $locale,
        ?string $group,
        ?string $namespace,
        ?array $meta,
    ): TranslationSet {
        return app($this->translation_contents_to_translation_set_action)
            ->usingDriver($this)
            ->usingProject($project)
            ->usingBranch($branch)
            ->usingLanguageDirectories($this->languageDirectories($project, $branch))
            ->shouldIgnorePackageTranslations($this->ignore_package_translations)
            ->shouldIgnoreVendorTranslations($this->ignore_vendor_translations)
            ->execute($contents, $locale, $group, $namespace, $meta);
    }

    /* Translation set
    ------------------------------------------------*/

    /**
     * Count the amount of translation sets to be retrieved.
     *
     * @param (callable(string $locale, ?string $group, ?string $namespace): bool)|null $filter
     * @param (callable(TranslationSet $translationSet): void)|null $onSkipped
     */
    public function countTranslationSets(
        ProjectConfiguration $project,
        Branch $branch,
        ?callable $filter = null,
        ?callable $onSkipped = null,
    ): int {
        return app($this->count_translation_sets_action)
            ->usingDriver($this)
            ->usingProject($project)
            ->usingBranch($branch)
            ->usingFilter($filter)
            ->onSkipped($onSkipped)
            ->usingLanguageDirectories($this->languageDirectories($project, $branch))
            ->shouldIgnorePackageTranslations($this->ignore_package_translations)
            ->shouldIgnoreVendorTranslations($this->ignore_vendor_translations)
            ->execute();
    }

    /**
     * Get a collection of `\Transl\Support\TranslationSet`s.
     *
     * @param (callable(string $locale, ?string $group, ?string $namespace): bool)|null $filter
     * @param (callable(TranslationSet $translationSet): void)|null $onSkipped
     * @return iterable<TranslationSet>
     */
    public function getTranslationSets(
        ProjectConfiguration $project,
        Branch $branch,
        ?callable $filter = null,
        ?callable $onSkipped = null,
    ): iterable {
        return app($this->get_translation_sets_action)
            ->usingDriver($this)
            ->usingProject($project)
            ->usingBranch($branch)
            ->usingFilter($filter)
            ->onSkipped($onSkipped)
            ->usingLanguageDirectories($this->languageDirectories($project, $branch))
            ->shouldIgnorePackageTranslations($this->ignore_package_translations)
            ->shouldIgnoreVendorTranslations($this->ignore_vendor_translations)
            ->execute();
    }

    /**
     * Retrieves a `\Transl\Support\TranslationSet`.
     */
    public function getTranslationSet(
        ProjectConfiguration $project,
        Branch $branch,
        string $locale,
        ?string $group,
        ?string $namespace,
        ?array $meta,
    ): TranslationSet {
        $contents = $this->getTranslationContents(
            $project,
            $branch,
            $locale,
            $group,
            $namespace,
        );

        return $this->translationContentsToTranslationSet(
            $project,
            $branch,
            $contents,
            $locale,
            $group,
            $namespace,
            $meta,
        );
    }

    /**
     * Stores a `\Transl\Support\TranslationSet` in a way
     * that should be readable to a Laravel translation loader.
     */
    public function saveTranslationSet(ProjectConfiguration $project, Branch $branch, TranslationSet $set): void
    {
        app($this->save_translation_set_action)
            ->usingDriver($this)
            ->usingProject($project)
            ->usingBranch($branch)
            ->usingTranslationSet($set)
            ->usingLanguageDirectories($this->languageDirectories($project, $branch))
            ->shouldIgnorePackageTranslations($this->ignore_package_translations)
            ->shouldIgnoreVendorTranslations($this->ignore_vendor_translations)
            ->execute();
    }

    /* Tracked translation set
    ------------------------------------------------*/

    /**
     * Get a `\Transl\Support\TranslationSet` that has
     * previously been pushed to Transl, thus, "tracked" by Transl.
     * This translation set will be used in determining conflicts.
     */
    public function getTrackedTranslationSet(ProjectConfiguration $project, Branch $branch, TranslationSet $set): ?TranslationSet
    {
        return app($this->get_tracked_translation_set_action)
            ->usingDriver($this)
            ->usingProject($project)
            ->usingBranch($branch)
            ->usingLanguageDirectories($this->languageDirectories($project, $branch))
            ->shouldIgnorePackageTranslations($this->ignore_package_translations)
            ->shouldIgnoreVendorTranslations($this->ignore_vendor_translations)
            ->execute($set);
    }

    /**
     * Stores a `\Transl\Support\TranslationSet` in a way
     * that would allow it to be reconstructed back (using `TranslationSet::from` for example).
     */
    public function saveTrackedTranslationSet(ProjectConfiguration $project, Branch $branch, TranslationSet $set): void
    {
        app($this->save_tracked_translation_set_action)
            ->usingDriver($this)
            ->usingProject($project)
            ->usingBranch($branch)
            ->usingLanguageDirectories($this->languageDirectories($project, $branch))
            ->shouldIgnorePackageTranslations($this->ignore_package_translations)
            ->shouldIgnoreVendorTranslations($this->ignore_vendor_translations)
            ->execute($set);
    }

    /* Helpers
    ------------------------------------------------*/

    public function defaultLanguageDirectories(ProjectConfiguration $project, Branch $branch): array
    {
        return [lang_path()];
    }

    public function filesystem(): Filesystem
    {
        return app(Filesystem::class);
    }

    public function translationLoader(): Loader
    {
        $loader = $this->translator()->getLoader();

        $this->ensureTranslationLoaderIsSupported($loader);

        return $loader;
    }

    public function getTrackedTranslationSetPath(
        ProjectConfiguration $project,
        Branch $branch,
        TranslationSet $set,
    ): ?string {
        $base = $project->options->transl_directory;

        if (!$base) {
            return null;
        }

        return "{$base}/{$project->auth_key}/{$branch->name}/tracked/{$set->trackingKey()}.json";
    }

    protected function translator(): Translator
    {
        return app('translator');
    }

    protected function ensureTranslationLoaderIsSupported(Loader $loader): void
    {
        if ($loader instanceof FileLoader) {
            return;
        }

        throw UnsupportedTranslationLoader::make($loader::class, static::class, FileLoader::class);
    }

    protected function languageDirectories(ProjectConfiguration $project, Branch $branch): array
    {
        return is_null($this->language_directories)
            ? $this->defaultLanguageDirectories($project, $branch)
            : $this->language_directories;
    }
}
