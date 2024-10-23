<?php

declare(strict_types=1);

use Transl\Facades\Transl;
use Transl\Support\Branch;
use Illuminate\Support\Arr;
use Transl\Config\Configuration;
use Illuminate\Support\Collection;
use Transl\Support\TranslationSet;
use Illuminate\Http\Client\Request;
use Transl\Support\TranslationLine;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Transl\Commands\TranslPullCommand;
use Illuminate\Support\Facades\Artisan;
use Transl\Config\ProjectConfiguration;
use Transl\Support\TranslationLineCollection;
use Transl\Actions\Commands\PullCommandAction;
use Transl\Config\Enums\BranchingConflictResolutionEnum;

beforeEach(function (): void {
    app()->setBasePath($this->getTestSupportDirectory('.to-delete/TranslPullCommandTest'));

    config()->set('transl.defaults.project_options.transl_directory', storage_path('app/.transl'));

    Configuration::refreshInstance(config('transl'));

    app(Filesystem::class)->copyDirectory($this->getTestSupportDirectory('lang'), lang_path());

    app()->bind(TranslationSet::class, function (Application $app, array $params): TranslationSet {
        return new TranslationSet(
            ...Arr::except($params, ['meta']),
            meta: [
                'translation_file' => [
                    'full_path' => $this->helpers()
                        ->translationSet()
                        ->determineTranslationFileFullPath(...Arr::except($params, ['lines', 'meta'])),
                ],
            ],
        );
    });

    File::swap(new class () extends Filesystem {
        public function updateTranslationSet(TranslationSet $set, array|string $search, array|string $replace)
        {
            $path = $set->meta['translation_file']['full_path'];

            $contents = $this->get($path);
            $contents = str_replace($search, $replace, $contents);

            return $this->put($path, $contents);
        }
    });

    $this->defaultResponses = json_decode(
        file_get_contents($this->getFixtureDirectory('pull_stub_responses.json')),
        true,
    );

    $this->pulledTranslationSets = static fn (): Collection => (
        collect(Http::recorded())
            ->flatten()
            ->filter(static fn (Request|Response $item): bool => $item instanceof Response)
            ->map(static fn (Response $response): array => $response->json('data'))
            ->flatten(1)
            ->map(static fn (array $set): TranslationSet => TranslationSet::new(
                locale: $set['attributes']['locale'],
                group: $set['attributes']['group'],
                namespace: $set['attributes']['namespace'],
                lines: TranslationLineCollection::make(
                    array_map(static function (array $line): TranslationLine {
                        return TranslationLine::make(
                            key: $line['attributes']['key'],
                            value: $line['attributes']['value'],
                            meta: $line['meta'],
                        );
                    }, $set['relations']['lines']['data']),
                ),
                meta: $set['meta'],
            ))
    );

    $this->getTranslationSets = static fn (ProjectConfiguration $project, Branch $branch): Collection => (
        collect(app($project->drivers->toBase()->keys()->first())->getTranslationSets($project, $branch))
    );

    $this->trackTranslationSet = static function (ProjectConfiguration $project, Branch $branch, TranslationSet $set): void {
        $directory = "{$project->options->transl_directory}/{$project->auth_key}/{$branch->name}/tracked";
        $fullPath = "{$directory}/{$set->trackingKey()}.json";

        app(Filesystem::class)->ensureDirectoryExists(dirname($fullPath));

        app(Filesystem::class)->put("{$directory}/{$set->trackingKey()}.json", json_encode($set->toArray()));
    };
});

afterEach(function (): void {
    app(Filesystem::class)->deleteDirectory($this->getTestSupportDirectory('.to-delete'));

    app()->setBasePath($this->getTestSupportDirectory());

    config()->set('transl.defaults.project_options.transl_directory', storage_path('app/.transl'));

    Configuration::refreshInstance(config('transl'));
});

it('works', function (): void {
    $this->defaultResponses = [
        'https://api.transl.me/v0/commands/yolo/pull' => [
            ...$this->defaultResponses['https://api.transl.me/v0/commands/yolo/pull'],
            'pagination' => [
                ...$this->defaultResponses['https://api.transl.me/v0/commands/yolo/pull']['pagination'],
                'attributes' => [
                    ...$this->defaultResponses['https://api.transl.me/v0/commands/yolo/pull']['pagination']['attributes'],
                    'has_more_pages' => false,
                ],
            ],
        ],
    ];

    Http::fake($this->defaultResponses);

    $project = Transl::config()->defaults()->project;
    $project = Transl::config()->projects()->whereAuthKeyOrName($project)->first();
    $branch = Branch::asProvided('yolo');
    $conflictResolution = BranchingConflictResolutionEnum::MERGE_AND_IGNORE;

    $currentSets = $this->getTranslationSets->__invoke($project, $branch);

    foreach ($currentSets as $set) {
        $this->trackTranslationSet->__invoke($project, $branch, $set);
    }

    $conflictingSet = $currentSets->first(static function (TranslationSet $set): bool {
        return (
            $set->locale === 'en'
            && $set->group === 'auth'
            && $set->namespace === null
        );
    });
    $conflictingLine = $conflictingSet->lines->firstWhere('key', 'failed_bis');

    app(Filesystem::class)->updateTranslationSet(
        $conflictingSet,
        $conflictingLine->value,
        "[UPDATE ON CURRENT] {$conflictingLine->value}",
    );

    expect(
        str_contains(file_get_contents($conflictingSet->meta['translation_file']['full_path']), '[UPDATE ON CURRENT]'),
    )->toEqual(true);

    expect(
        Artisan::call(TranslPullCommand::class, [
            '--branch' => $branch->name,
            '--conflicts' => $conflictResolution->value,
        ]),
    )->toEqual(TranslPullCommand::SUCCESS);
    // expect(Artisan::output())->toMatchConsoleOutput();

    expect(
        str_contains(file_get_contents($conflictingSet->meta['translation_file']['full_path']), '[UPDATE ON CURRENT]'),
    )->toEqual(true);

    expect($this->pulledTranslationSets->__invoke()->isEmpty())->toEqual(false);
});

it('uses `PullCommandAction` to pull translation sets from Transl', function (): void {
    app()->singleton(PullCommandAction::class, function (): PullCommandAction {
        return new class () extends PullCommandAction {
            public readonly bool $used;

            public function execute(
                ProjectConfiguration $project,
                Branch $branch,
                ?BranchingConflictResolutionEnum $conflictResolution = null,
            ): void {
                $this->used = true;
            }
        };
    });

    Artisan::call(TranslPullCommand::class);

    expect(app(PullCommandAction::class)->used)->toEqual(true);
});
