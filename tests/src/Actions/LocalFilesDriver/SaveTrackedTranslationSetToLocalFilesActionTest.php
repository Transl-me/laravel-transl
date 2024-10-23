<?php

declare(strict_types=1);

use Transl\Facades\Transl;
use Transl\Support\Branch;
use Transl\Config\Configuration;
use Transl\Support\TranslationSet;
use Transl\Drivers\LocalFilesDriver;
use Illuminate\Filesystem\Filesystem;
use Transl\Support\TranslationLineCollection;
use Transl\Actions\LocalFilesDriver\SaveTrackedTranslationSetToLocalFilesAction;

it('does nothing when the "transl directory" is disabled', function (): void {
    $previousConfiguration = Configuration::setInstance(Configuration::new(config('transl')));

    $translDirectory = config('transl.defaults.project_options.transl_directory');

    config()->set('transl.defaults.project_options.transl_directory', false);

    Configuration::setInstance(Configuration::new(config('transl')));

    $project = Transl::config()->projects()->first();
    $branch = Branch::asCurrent('test');

    $translationSet = TranslationSet::new(
        locale: 'en',
        group: 'test',
        namespace: 'example',
        lines: TranslationLineCollection::make(),
        meta: null,
    );

    $path = "{$translDirectory}/{$project->auth_key}/{$branch->name}/tracked/{$translationSet->trackingKey()}.json";

    $isntance = (new SaveTrackedTranslationSetToLocalFilesAction())
        ->usingProject($project)
        ->usingBranch($branch)
        ->usingDriver(new LocalFilesDriver());

    $isntance->execute($translationSet);

    expect(file_exists($path))->toEqual(false);

    Configuration::setInstance($previousConfiguration);
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

    $isntance = (new SaveTrackedTranslationSetToLocalFilesAction())
        ->usingProject($project)
        ->usingBranch($branch)
        ->usingDriver(new LocalFilesDriver());

    expect(file_exists($path))->toEqual(false);

    $isntance->execute($translationSet);

    expect(file_exists($path))->toEqual(true);

    expect(app(Filesystem::class)->json($path))->toEqual($translationSet->toArray());

    app(Filesystem::class)->deleteDirectory($translDirectory);
});
