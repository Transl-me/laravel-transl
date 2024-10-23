<?php

declare(strict_types=1);

use Transl\Facades\Transl;
use Transl\Support\Branch;
use Transl\Config\Configuration;
use Transl\Support\TranslationSet;
use Transl\Drivers\LocalFilesDriver;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use Transl\Exceptions\LocalFilesDriver\FoundDisallowedProjectLocale;
use Transl\Exceptions\LocalFilesDriver\CouldNotOpenLanguageDirectory;
use Transl\Actions\LocalFilesDriver\GetTranslationSetsFromLocalFilesAction;

it('works', function (): void {
    $files = collect(app(Filesystem::class)->allFiles($this->getLangDirectory()));
    $isntance = (new GetTranslationSetsFromLocalFilesAction())
        ->usingProject(Transl::config()->projects()->first())
        ->usingBranch(Branch::asCurrent('test'))
        ->usingDriver(new LocalFilesDriver())
        ->usingLanguageDirectories([$this->getLangDirectory()])
        ->shouldIgnorePackageTranslations(false)
        ->shouldIgnoreVendorTranslations(false);
    $result = $isntance->execute();

    expect($result instanceof Generator)->toEqual(true);

    /** @var TranslationSet[] $sets */
    $sets = [];

    foreach ($result as $set) {
        expect($set instanceof TranslationSet)->toEqual(true);

        $sets[] = $set;

        $translationFileRelativePath = $this->helpers()->translationSet()->determineTranslationFileRelativePath($set);
        $translationFileFullPath = "{$this->getLangDirectory()}/{$translationFileRelativePath}";

        expect(file_exists($translationFileFullPath))->toEqual(true);
        expect(is_file($translationFileFullPath))->toEqual(true);

        $files = $files->filter(static function (SplFileInfo $file) use ($translationFileFullPath): bool {
            return $translationFileFullPath !== str_replace(DIRECTORY_SEPARATOR, '/', $file->getRealPath());
        });
    }

    expect($files->isEmpty())->toEqual(true);

    expect(
        collect($sets)->sortBy(fn (TranslationSet $set) => $set->trackingKey())->values()->all(),
    )->toMatchStandardizedSnapshot();
});

it('throws an exception when the provided language directory cannot be opened', function (): void {
    $isntance = (new GetTranslationSetsFromLocalFilesAction())
        ->usingProject(Transl::config()->projects()->first())
        ->usingBranch(Branch::asCurrent('test'))
        ->usingDriver(new LocalFilesDriver())
        ->usingLanguageDirectories(['nope'])
        ->shouldIgnorePackageTranslations(false)
        ->shouldIgnoreVendorTranslations(false);
    $result = $isntance->execute();

    expect(static fn () => iterator_to_array($result))->toThrow(CouldNotOpenLanguageDirectory::class);
});

it('can ignore package translation files', function (): void {
    $isntance = (new GetTranslationSetsFromLocalFilesAction())
        ->usingProject(Transl::config()->projects()->first())
        ->usingBranch(Branch::asCurrent('test'))
        ->usingDriver(new LocalFilesDriver())
        ->usingLanguageDirectories([$this->getLangDirectory()])
        ->shouldIgnorePackageTranslations(true)
        ->shouldIgnoreVendorTranslations(false);
    $result = $isntance->execute();

    $namespacedSets = collect($result)->filter(static function (TranslationSet $set): bool {
        return (bool) $set->namespace;
    });

    expect($namespacedSets->isEmpty())->toEqual(true);
});

it('can ignore vendor translation files', function (): void {
    $isntance = (new GetTranslationSetsFromLocalFilesAction())
        ->usingProject(Transl::config()->projects()->first())
        ->usingBranch(Branch::asCurrent('test'))
        ->usingDriver(new LocalFilesDriver())
        ->usingLanguageDirectories([$this->getPackageDirectory('/vendor/laravel/framework/src/Illuminate/Translation/lang')])
        ->shouldIgnorePackageTranslations(false)
        ->shouldIgnoreVendorTranslations(false);
    $result = $isntance->execute();

    expect(collect($result)->isEmpty())->toEqual(false);

    $result = $isntance->shouldIgnoreVendorTranslations(true)->execute();

    expect(collect($result)->isEmpty())->toEqual(true);
});

it('can throw an exception when a disallowed locale is encountered', function (): void {
    $previousConfiguration = Configuration::setInstance(Configuration::new(config('transl')));

    config()->set('transl.defaults.project_options.locale.allowed', ['de']);
    config()->set('transl.defaults.project_options.locale.throw_on_disallowed_locale', true);

    Configuration::setInstance(Configuration::new(config('transl')));

    $isntance = (new GetTranslationSetsFromLocalFilesAction())
        ->usingProject(Transl::config()->projects()->first())
        ->usingBranch(Branch::asCurrent('test'))
        ->usingDriver(new LocalFilesDriver())
        ->usingLanguageDirectories([$this->getLangDirectory()])
        ->shouldIgnorePackageTranslations(false)
        ->shouldIgnoreVendorTranslations(false);
    $result = $isntance->execute();

    expect(static fn () => iterator_to_array($result))->toThrow(FoundDisallowedProjectLocale::class);

    Configuration::setInstance($previousConfiguration);
});

it('can ignore encountered disallowed locales', function (): void {
    $previousConfiguration = Configuration::setInstance(Configuration::new(config('transl')));

    config()->set('transl.defaults.project_options.locale.allowed', ['de']);
    config()->set('transl.defaults.project_options.locale.throw_on_disallowed_locale', false);

    Configuration::setInstance(Configuration::new(config('transl')));

    $isntance = (new GetTranslationSetsFromLocalFilesAction())
        ->usingProject(Transl::config()->projects()->first())
        ->usingBranch(Branch::asCurrent('test'))
        ->usingDriver(new LocalFilesDriver())
        ->usingLanguageDirectories([$this->getLangDirectory()])
        ->shouldIgnorePackageTranslations(false)
        ->shouldIgnoreVendorTranslations(false);
    $result = $isntance->execute();

    expect(collect($result)->isEmpty())->toEqual(true);

    Configuration::setInstance($previousConfiguration);
});

it("doesn't ignore encountered allowed locales", function (): void {
    $previousConfiguration = Configuration::setInstance(Configuration::new(config('transl')));

    config()->set('transl.defaults.project_options.locale.allowed', ['fr']);
    config()->set('transl.defaults.project_options.locale.throw_on_disallowed_locale', false);

    Configuration::setInstance(Configuration::new(config('transl')));

    $isntance = (new GetTranslationSetsFromLocalFilesAction())
        ->usingProject(Transl::config()->projects()->first())
        ->usingBranch(Branch::asCurrent('test'))
        ->usingDriver(new LocalFilesDriver())
        ->usingLanguageDirectories([$this->getLangDirectory()])
        ->shouldIgnorePackageTranslations(false)
        ->shouldIgnoreVendorTranslations(false);
    $result = $isntance->execute();

    expect(collect($result)->pluck('locale')->unique()->values()->all())->toEqual(['fr']);

    Configuration::setInstance($previousConfiguration);
});
