<?php

declare(strict_types=1);

use Transl\Facades\Transl;
use Transl\Support\Branch;
use Transl\Config\Configuration;
use Illuminate\Support\Collection;
use Transl\Support\Push\PushBatch;
use Transl\Support\TranslationSet;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Filesystem\Filesystem;
use Transl\Commands\TranslInitCommand;
use Illuminate\Support\Facades\Artisan;
use Transl\Config\ProjectConfiguration;
use Transl\Actions\Commands\InitCommandAction;

beforeEach(function (): void {
    app()->setBasePath($this->getTestSupportDirectory('.to-delete/TranslInitCommandTest'));

    config()->set('transl.defaults.project_options.transl_directory', storage_path('app/.transl'));

    Configuration::refreshInstance(config('transl'));

    app(Filesystem::class)->copyDirectory($this->getTestSupportDirectory('lang'), lang_path());

    $this->getTranslationSets = static fn (ProjectConfiguration $project, Branch $branch): Collection => (
        collect(app($project->drivers->toBase()->keys()->first())->getTranslationSets($project, $branch))
    );
});

afterEach(function (): void {
    app(Filesystem::class)->deleteDirectory($this->getTestSupportDirectory('.to-delete'));

    app()->setBasePath($this->getTestSupportDirectory());

    config()->set('transl.defaults.project_options.transl_directory', storage_path('app/.transl'));

    Configuration::refreshInstance(config('transl'));
});

it('works', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/init/start' => Http::response(),
        'https://api.transl.me/v0/commands/init/end' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $project = Transl::config()->defaults()->project;
    $project = Transl::config()->projects()->whereAuthKeyOrName($project)->first();
    $branch = Branch::asProvided('yolo');

    $trackedDirectory = "{$project->options->transl_directory}/{$project->auth_key}/{$branch->name}/tracked";

    expect(file_exists($trackedDirectory))->toEqual(false);

    expect(Artisan::call(TranslInitCommand::class, ['--branch' => $branch->name]))->toEqual(TranslInitCommand::SUCCESS);
    // expect(Artisan::output())->toMatchConsoleOutput();

    expect(file_exists($trackedDirectory))->toEqual(true);

    $translationSets = $this->getTranslationSets->__invoke($project, $branch);
    $sentTranslationSets = collect(Http::recorded())
        ->flatten()
        ->filter(static fn (Request|Response $item): bool => $item instanceof Request)
        ->filter(static fn (Request $request): bool => str_ends_with($request->url(), '/push'))
        ->map(static fn (Request $request): array => $request->data())
        ->flatMap(static fn (array $data): array => $data['chunk']['translation_sets'])
        ->map(static fn (array $set): TranslationSet => TranslationSet::from($set));

    expect(
        $translationSets
            ->map(static fn (TranslationSet $set): string => $set->trackingKey())
            ->sort()
            ->values()
            ->all(),
    )->toEqual(
        $sentTranslationSets
            ->map(static fn (TranslationSet $set): string => $set->trackingKey())
            ->sort()
            ->values()
            ->all(),
    );

    foreach ($translationSets as $set) {
        expect(file_exists("{$trackedDirectory}/{$set->trackingKey()}.json"))->toEqual(true);
    }
});

it('uses `InitCommandAction` to initialize a project on Transl', function (): void {
    app()->singleton(InitCommandAction::class, function (): InitCommandAction {
        return new class () extends InitCommandAction {
            public readonly bool $used;

            public function execute(
                ProjectConfiguration $project,
                Branch $branch,
                ?PushBatch $batch = null,
                array $meta = [],
            ): void {
                $this->used = true;
            }
        };
    });

    Artisan::call(TranslInitCommand::class);

    expect(app(InitCommandAction::class)->used)->toEqual(true);
});
