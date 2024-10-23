<?php

declare(strict_types=1);

use Transl\Facades\Transl;
use Transl\Support\Branch;
use Transl\Config\Configuration;
use Transl\Support\TranslationSet;
use Transl\Drivers\LocalFilesDriver;
use Illuminate\Filesystem\Filesystem;
use Transl\Support\TranslationLineCollection;
use Transl\Actions\LocalFilesDriver\GetTrackedTranslationSetFromLocalFilesAction;

it('returns "null" when the "transl directory" is disabled', function (): void {
    $previousConfiguration = Configuration::setInstance(Configuration::new(config('transl')));

    config()->set('transl.defaults.project_options.transl_directory', false);

    Configuration::setInstance(Configuration::new(config('transl')));

    $translationSet = TranslationSet::new(
        locale: 'en',
        group: 'test',
        namespace: 'example',
        lines: TranslationLineCollection::make(),
        meta: null,
    );

    $isntance = (new GetTrackedTranslationSetFromLocalFilesAction())
        ->usingProject(Transl::config()->projects()->first())
        ->usingBranch(Branch::asCurrent('test'))
        ->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute($translationSet);

    expect($result)->toEqual(null);

    Configuration::setInstance($previousConfiguration);
});

it('returns "null" when the provided TranslationSet is not yet tracked', function (): void {
    $translationSet = TranslationSet::new(
        locale: 'en',
        group: 'test',
        namespace: 'example',
        lines: TranslationLineCollection::make(),
        meta: null,
    );

    $isntance = (new GetTrackedTranslationSetFromLocalFilesAction())
        ->usingProject(Transl::config()->projects()->first())
        ->usingBranch(Branch::asCurrent('test'))
        ->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute($translationSet);

    expect($result)->toEqual(null);
});

it('works', function (): void {
    $project = Transl::config()->projects()->first();
    $branch = Branch::asCurrent('test');

    $translDirectory = config('transl.defaults.project_options.transl_directory');

    $translationSet = TranslationSet::new(
        locale: 'en',
        group: 'test',
        namespace: 'example',
        lines: TranslationLineCollection::make(),
        meta: null,
    );

    $path = "{$translDirectory}/{$project->auth_key}/{$branch->name}/tracked/{$translationSet->trackingKey()}.json";

    app(Filesystem::class)->ensureDirectoryExists(dirname($path));
    app(Filesystem::class)->put($path, json_encode($translationSet->toArray()));

    $isntance = (new GetTrackedTranslationSetFromLocalFilesAction())
        ->usingProject($project)
        ->usingBranch($branch)
        ->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute($translationSet);

    expect($result->toArray())->toEqual($translationSet->toArray());

    app(Filesystem::class)->deleteDirectory($translDirectory);
});
