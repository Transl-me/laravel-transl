<?php

declare(strict_types=1);

use Transl\Support\Git;
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
use Illuminate\Support\Facades\Process;
use Transl\Config\ProjectConfiguration;
use Transl\Actions\Commands\InitCommandAction;
use Transl\Actions\Commands\PushCommandAction;
use Transl\Actions\Commands\AbstractCommandAction;

beforeEach(function (): void {
    app()->setBasePath($this->getTestSupportDirectory('.to-delete/InitCommandActionTest'));

    config()->set('transl.defaults.project_options.transl_directory', storage_path('app/.transl'));

    Configuration::refreshInstance(config('transl'));

    app(Filesystem::class)->copyDirectory($this->getTestSupportDirectory('lang'), lang_path());

    $this->getTranslationSets = static fn (ProjectConfiguration $project, Branch $branch): Collection => (
        collect(app($project->drivers->toBase()->keys()->first())->getTranslationSets($project, $branch))
    );

    PushBatch::setMaxPoolSize(1);
    PushBatch::setMaxChunkSize(1);
});

afterEach(function (): void {
    app(Filesystem::class)->deleteDirectory($this->getTestSupportDirectory('.to-delete'));

    app()->setBasePath($this->getTestSupportDirectory());

    config()->set('transl.defaults.project_options.transl_directory', storage_path('app/.transl'));

    Configuration::refreshInstance(config('transl'));

    PushBatch::resetDefaultMaxPoolAndChunkSizes();
});

it('extends `AbstractCommandAction`', function (): void {
    expect(is_subclass_of(InitCommandAction::class, AbstractCommandAction::class))->toEqual(true);
});

it('executes for a given project', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/init/start' => Http::response(),
        'https://api.transl.me/v0/commands/init/end' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new InitCommandAction());

    $project = Transl::config()->projects()->first();
    $branch = Branch::asCurrent('yolo');

    $action->execute($project, $branch);

    expect($action->project()->auth_key)->toEqual($project->auth_key);

    Http::assertSentCount($this->getTranslationSets->__invoke($project, $branch)->count() + 3);
});

it('executes for a given branch', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/init/start' => Http::response(),
        'https://api.transl.me/v0/commands/init/end' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new InitCommandAction());

    $project = Transl::config()->projects()->first();
    $branch = Branch::asCurrent('yolo');

    $action->execute($project, $branch);

    expect($action->branch()->name)->toEqual($branch->name);

    Http::assertSentCount($this->getTranslationSets->__invoke($project, $branch)->count() + 3);
});

it('pushes the translation sets to Transl', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/init/start' => Http::response(),
        'https://api.transl.me/v0/commands/init/end' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new InitCommandAction());

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch());
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
});

it('uses `PushCommandAction` to push translation sets to Transl', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/init/start' => Http::response(),
        'https://api.transl.me/v0/commands/init/end' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    app()->singleton(PushCommandAction::class, function (): PushCommandAction {
        return new class () extends PushCommandAction {
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

    (new InitCommandAction())->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    expect(app(PushCommandAction::class)->used)->toEqual(true);
});

it('sets the necessary HTTP headers & custom Transl HTTP headers', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/init/start' => Http::response(),
        'https://api.transl.me/v0/commands/init/end' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $project = Transl::config()->projects()->first();
    $branch = Branch::asCurrent('yolo');

    (new InitCommandAction())->execute($project, $branch);

    $versions = Transl::versions();
    $packageName = Transl::PACKAGE_NAME;

    $userAgentPackageName = str_replace('/', '___', $packageName);
    $userAgent = "{$userAgentPackageName}/{$versions->package} laravel/{$versions->laravel} php/{$versions->php}";

    Http::assertSent(static function (Request $request) use ($project, $branch, $versions, $packageName, $userAgent): bool {
        return $request->hasHeaders([
            'User-Agent' => $userAgent,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',

            'Authorization' => "Bearer {$project->auth_key}",

            'X-Transl-Branch-Name' => $branch->name,
            'X-Transl-Branch-Provenance' => $branch->provenance(),

            'X-Transl-Package-Name' => $packageName,
            'X-Transl-Package-Version' => $versions->package,
            'X-Transl-Framework-Name' => 'Laravel',
            'X-Transl-Framework-Version' => $versions->laravel,
            'X-Transl-Language-Name' => 'PHP',
            'X-Transl-Language-Version' => $versions->php,
        ]);
    });
});

it('sends the necessary data on init start', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/init/start' => Http::response(),
        'https://api.transl.me/v0/commands/init/end' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $project = Transl::config()->projects()->first();
    $branch = Branch::asCurrent('yolo');

    (new InitCommandAction())->execute($project, $branch);

    /** @var Request $startRequest */
    $startRequest = collect(Http::recorded())
        ->flatten()
        ->filter(static fn (Request|Response $item): bool => $item instanceof Request)
        ->filter(static fn (Request $request): bool => str_contains($request->url(), '/init/start'))
        ->first();

    expect($startRequest->data())->toEqual([
        'locale' => [
            'default' => 'en',
            'fallback' => 'en',
        ],
        'branching' => [
            'default_branch_name' => 'default',
        ],
    ]);
});

it('sends a fallback default branch if none could be determined', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/init/start' => Http::response(),
        'https://api.transl.me/v0/commands/init/end' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    Process::fake([
        Git::getDefaultConfiguredBranchNameCommand() => '',
    ]);

    config()->set('transl.defaults.project_options.branching.default_branch_name', null);

    Configuration::refreshInstance(config('transl'));

    $project = Transl::config()->projects()->first();
    $branch = Branch::asCurrent('yolo');

    (new InitCommandAction())->execute($project, $branch);

    /** @var Request $startRequest */
    $startRequest = collect(Http::recorded())
        ->flatten()
        ->filter(static fn (Request|Response $item): bool => $item instanceof Request)
        ->filter(static fn (Request $request): bool => str_contains($request->url(), '/init/start'))
        ->first();

    expect($startRequest->data()['branching']['default_branch_name'] === Transl::FALLBACK_BRANCH_NAME)->toEqual(true);
});
