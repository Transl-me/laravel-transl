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
use Transl\Config\ProjectConfiguration;
use Transl\Actions\Commands\PushCommandAction;
use Transl\Actions\Commands\AbstractCommandAction;

beforeEach(function (): void {
    app()->setBasePath($this->getTestSupportDirectory('.to-delete/PushCommandActionTest'));

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
    expect(is_subclass_of(PushCommandAction::class, AbstractCommandAction::class))->toEqual(true);
});

it('executes for a given project', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new PushCommandAction());

    $project = Transl::config()->projects()->first();
    $branch = Branch::asCurrent('yolo');

    $action->execute($project, $branch);

    expect($action->project()->auth_key)->toEqual($project->auth_key);

    Http::assertSentCount($this->getTranslationSets->__invoke($project, $branch)->count() + 1);
});

it('executes for a given branch', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new PushCommandAction());

    $project = Transl::config()->projects()->first();
    $branch = Branch::asCurrent('yolo');

    $action->execute($project, $branch);

    expect($action->branch()->name)->toEqual($branch->name);

    Http::assertSentCount($this->getTranslationSets->__invoke($project, $branch)->count() + 1);
});

it('pushes the translation sets to Transl', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new PushCommandAction());

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

it('transfers all locales when no filtering specified', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new PushCommandAction());

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch());
    $locales = $translationSets->pluck('locale')->unique()->sort()->values()->all();
    $sentLocales = collect(Http::recorded())
        ->flatten()
        ->filter(static fn (Request|Response $item): bool => $item instanceof Request)
        ->filter(static fn (Request $request): bool => str_ends_with($request->url(), '/push'))
        ->map(static fn (Request $request): array => $request->data())
        ->flatMap(static fn (array $data): array => collect($data['chunk']['translation_sets'])->pluck('locale')->all())
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($locales)->toEqual($sentLocales);
    expect($sentLocales)->toEqual(['en', 'fr']);

    Http::assertSentCount($translationSets->count() + 1);
});

it('transfers all groups when no filtering specified', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new PushCommandAction());

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch());
    $groups = $translationSets->pluck('group')->unique()->sort()->values()->all();
    $sentGroups = collect(Http::recorded())
        ->flatten()
        ->filter(static fn (Request|Response $item): bool => $item instanceof Request)
        ->filter(static fn (Request $request): bool => str_ends_with($request->url(), '/push'))
        ->map(static fn (Request $request): array => $request->data())
        ->flatMap(static fn (array $data): array => collect($data['chunk']['translation_sets'])->pluck('group')->all())
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($groups)->toEqual($sentGroups);
    expect($sentGroups)->toEqual(collect([
        'auth',
        'email',
        'flash',
        'value_types',
        'pages/dashboard/nav',
        'example',
        null,
    ])->sort()->values()->all());

    Http::assertSentCount($translationSets->count() + 1);
});

it('transfers all namespaces when no filtering specified', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new PushCommandAction());

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch());
    $namespaces = $translationSets->pluck('namespace')->unique()->sort()->values()->all();
    $sentNamespaces = collect(Http::recorded())
        ->flatten()
        ->filter(static fn (Request|Response $item): bool => $item instanceof Request)
        ->filter(static fn (Request $request): bool => str_ends_with($request->url(), '/push'))
        ->map(static fn (Request $request): array => $request->data())
        ->flatMap(static fn (array $data): array => collect($data['chunk']['translation_sets'])->pluck('namespace')->all())
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($namespaces)->toEqual($sentNamespaces);
    expect($sentNamespaces)->toEqual([null, 'some_package']);

    Http::assertSentCount($translationSets->count() + 1);
});

it('only transfers the specified locales', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new PushCommandAction())->acceptsLocales(['fr']);

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch())
        ->filter(static fn (TranslationSet $set): bool => $set->locale === 'fr');
    $locales = $translationSets->pluck('locale')->unique()->sort()->values()->all();
    $sentLocales = collect(Http::recorded())
        ->flatten()
        ->filter(static fn (Request|Response $item): bool => $item instanceof Request)
        ->filter(static fn (Request $request): bool => str_ends_with($request->url(), '/push'))
        ->map(static fn (Request $request): array => $request->data())
        ->flatMap(static fn (array $data): array => collect($data['chunk']['translation_sets'])->pluck('locale')->all())
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($locales)->toEqual($sentLocales);
    expect($sentLocales)->toEqual(['fr']);

    Http::assertSentCount($translationSets->count() + 1);
});

it('only transfers the specified groups', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new PushCommandAction())->acceptsGroups(['auth', null, 'pages/dashboard/nav']);

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch())
        ->filter(static fn (TranslationSet $set): bool => in_array($set->group, ['auth', null, 'pages/dashboard/nav'], true));
    $groups = $translationSets->pluck('group')->unique()->sort()->values()->all();
    $sentGroups = collect(Http::recorded())
        ->flatten()
        ->filter(static fn (Request|Response $item): bool => $item instanceof Request)
        ->filter(static fn (Request $request): bool => str_ends_with($request->url(), '/push'))
        ->map(static fn (Request $request): array => $request->data())
        ->flatMap(static fn (array $data): array => collect($data['chunk']['translation_sets'])->pluck('group')->all())
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($groups)->toEqual($sentGroups);
    expect($sentGroups)->toEqual(collect([
        'auth',
        'pages/dashboard/nav',
        null,
    ])->sort()->values()->all());

    Http::assertSentCount($translationSets->count() + 1);
});

it('only transfers the specified namespaces', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new PushCommandAction())->acceptsNamespaces([null]);

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch())
        ->filter(static fn (TranslationSet $set): bool => $set->namespace === null);
    $namespaces = $translationSets->pluck('namespace')->unique()->sort()->values()->all();
    $sentNamespaces = collect(Http::recorded())
        ->flatten()
        ->filter(static fn (Request|Response $item): bool => $item instanceof Request)
        ->filter(static fn (Request $request): bool => str_ends_with($request->url(), '/push'))
        ->map(static fn (Request $request): array => $request->data())
        ->flatMap(static fn (array $data): array => collect($data['chunk']['translation_sets'])->pluck('namespace')->all())
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($namespaces)->toEqual($sentNamespaces);
    expect($sentNamespaces)->toEqual([null]);

    Http::assertSentCount($translationSets->count() + 1);
});

it('rejects transfering the specified locales', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new PushCommandAction())->rejectsLocales(['fr']);

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch())
        ->reject(static fn (TranslationSet $set): bool => $set->locale === 'fr');
    $locales = $translationSets->pluck('locale')->unique()->sort()->values()->all();
    $sentLocales = collect(Http::recorded())
        ->flatten()
        ->filter(static fn (Request|Response $item): bool => $item instanceof Request)
        ->filter(static fn (Request $request): bool => str_ends_with($request->url(), '/push'))
        ->map(static fn (Request $request): array => $request->data())
        ->flatMap(static fn (array $data): array => collect($data['chunk']['translation_sets'])->pluck('locale')->all())
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($locales)->toEqual($sentLocales);
    expect($sentLocales)->toEqual(['en']);

    Http::assertSentCount($translationSets->count() + 1);
});

it('rejects transfering the specified groups', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new PushCommandAction())->rejectsGroups(['auth', null, 'pages/dashboard/nav']);

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch())
        ->reject(static fn (TranslationSet $set): bool => in_array($set->group, ['auth', null, 'pages/dashboard/nav'], true));
    $groups = $translationSets->pluck('group')->unique()->sort()->values()->all();
    $sentGroups = collect(Http::recorded())
        ->flatten()
        ->filter(static fn (Request|Response $item): bool => $item instanceof Request)
        ->filter(static fn (Request $request): bool => str_ends_with($request->url(), '/push'))
        ->map(static fn (Request $request): array => $request->data())
        ->flatMap(static fn (array $data): array => collect($data['chunk']['translation_sets'])->pluck('group')->all())
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($groups)->toEqual($sentGroups);
    expect($sentGroups)->toEqual(collect([
        'email',
        'flash',
        'value_types',
        'example',
    ])->sort()->values()->all());

    Http::assertSentCount($translationSets->count() + 1);
});

it('rejects transfering the specified namespaces', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new PushCommandAction())->rejectsNamespaces([null]);

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch())
        ->reject(static fn (TranslationSet $set): bool => $set->namespace === null);
    $namespaces = $translationSets->pluck('namespace')->unique()->sort()->values()->all();
    $sentNamespaces = collect(Http::recorded())
        ->flatten()
        ->filter(static fn (Request|Response $item): bool => $item instanceof Request)
        ->filter(static fn (Request $request): bool => str_ends_with($request->url(), '/push'))
        ->map(static fn (Request $request): array => $request->data())
        ->flatMap(static fn (array $data): array => collect($data['chunk']['translation_sets'])->pluck('namespace')->all())
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($namespaces)->toEqual($sentNamespaces);
    expect($sentNamespaces)->toEqual(['some_package']);

    Http::assertSentCount($translationSets->count() + 1);
});

it('favors rejecting over accepting specified locales', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new PushCommandAction())
        ->acceptsLocales(['fr'])
        ->rejectsLocales(['fr']);

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    Http::assertNothingSent();
});

it('favors rejecting over accepting specified groups', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new PushCommandAction())
        ->acceptsGroups(['auth', null, 'pages/dashboard/nav', 'example'])
        ->rejectsGroups(['auth', null, 'pages/dashboard/nav']);

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch())
        ->filter(static fn (TranslationSet $set): bool => $set->group === 'example');
    $groups = $translationSets->pluck('group')->unique()->sort()->values()->all();
    $sentGroups = collect(Http::recorded())
        ->flatten()
        ->filter(static fn (Request|Response $item): bool => $item instanceof Request)
        ->filter(static fn (Request $request): bool => str_ends_with($request->url(), '/push'))
        ->map(static fn (Request $request): array => $request->data())
        ->flatMap(static fn (array $data): array => collect($data['chunk']['translation_sets'])->pluck('group')->all())
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($groups)->toEqual($sentGroups);
    expect($sentGroups)->toEqual(['example']);

    Http::assertSentCount($translationSets->count() + 1);
});

it('favors rejecting over accepting specified namespaces', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new PushCommandAction())
        ->acceptsNamespaces([null])
        ->rejectsNamespaces([null]);

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    Http::assertNothingSent();
});

it('notifies of translation sets skipped base on locales filters', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $skipped = collect([]);

    $action = (new PushCommandAction())
        ->rejectsLocales(['fr'])
        ->onTranslationSetSkipped(static fn (TranslationSet $set) => $skipped->add($set));

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    expect($skipped->pluck('locale')->unique()->sort()->values()->all())->toEqual(['fr']);
});

it('notifies of translation sets skipped base on groups filters', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $skipped = collect([]);

    $action = (new PushCommandAction())
        ->rejectsGroups(['auth', null, 'pages/dashboard/nav'])
        ->onTranslationSetSkipped(static fn (TranslationSet $set) => $skipped->add($set));

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    expect($skipped->pluck('group')->unique()->sort()->values()->all())->toEqual(
        collect(['auth', null, 'pages/dashboard/nav'])->sort()->values()->all(),
    );
});

it('notifies of translation sets skipped base on namespaces filters', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $skipped = collect([]);

    $action = (new PushCommandAction())
        ->rejectsNamespaces([null])
        ->onTranslationSetSkipped(static fn (TranslationSet $set) => $skipped->add($set));

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    expect($skipped->pluck('namespace')->unique()->sort()->values()->all())->toEqual([null]);
});

it('notifies of handled translation sets', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $handled = collect([]);

    $action = (new PushCommandAction())
        ->acceptsLocales(['fr'])
        ->onTranslationSetHandled(static fn (TranslationSet $set) => $handled->add($set));

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    expect($handled->pluck('locale')->unique()->sort()->values()->all())->toEqual(['fr']);
});

it('tracks handled translation sets', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $action = (new PushCommandAction());

    $project = Transl::config()->projects()->first();
    $branch = Branch::asCurrent('yolo');

    $directory = "{$project->options->transl_directory}/{$project->auth_key}/{$branch->name}/tracked";

    expect(file_exists($directory))->toEqual(false);

    $action->execute($project, $branch);

    expect(file_exists($directory))->toEqual(true);

    $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch());

    foreach ($translationSets as $set) {
        expect(file_exists("{$directory}/{$set->trackingKey()}.json"))->toEqual(true);
    }
});

it('does not track skipped translation sets', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $skipped = collect([]);
    $handled = collect([]);

    $action = (new PushCommandAction())
        ->rejectsLocales(['fr'])
        ->onTranslationSetSkipped(static fn (TranslationSet $set) => $skipped->add($set))
        ->onTranslationSetHandled(static fn (TranslationSet $set) => $handled->add($set));

    $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    expect($skipped->isEmpty())->toEqual(false);
    expect($handled->isEmpty())->toEqual(false);

    $directory = "{$action->project()->options->transl_directory}/{$action->project()->auth_key}/{$action->branch()->name}/tracked";

    foreach ($skipped as $set) {
        expect(file_exists("{$directory}/{$set->trackingKey()}.json"))->toEqual(false);
    }

    foreach ($handled as $set) {
        expect(file_exists("{$directory}/{$set->trackingKey()}.json"))->toEqual(true);
    }
});

it('sets the necessary HTTP headers & custom Transl HTTP headers', function (): void {
    Http::fake([
        'https://api.transl.me/v0/commands/yolo/push' => Http::response(),
        'https://api.transl.me/v0/commands/yolo/push/end' => Http::response(),
    ]);

    $project = Transl::config()->projects()->first();
    $branch = Branch::asCurrent('yolo');

    (new PushCommandAction())->execute($project, $branch);

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
