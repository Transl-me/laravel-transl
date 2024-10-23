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
use Transl\Config\ProjectConfiguration;
use Transl\Support\TranslationLinesDiffing;
use Transl\Support\TranslationLineCollection;
use Transl\Actions\Commands\PullCommandAction;
use Transl\Support\LocaleFilesystem\LangFilePath;
use Transl\Actions\Commands\AbstractCommandAction;
use Transl\Config\Enums\BranchingConflictResolutionEnum;
use Transl\Exceptions\Branching\CouldNotResolveConflictWhilePulling;

beforeEach(function (): void {
    app()->setBasePath($this->getTestSupportDirectory('.to-delete/PullCommandActionTest'));

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
        protected array $saved = [];

        public function put($path, $contents, $lock = false)
        {
            $this->saved[] = [
                'path' => $path,
                'contents' => $contents,
                'lock' => $lock,
            ];

            return parent::put($path, $contents, $lock);
        }

        public function updateTranslationSet(TranslationSet $set, array|string $search, array|string $replace)
        {
            $path = $set->meta['translation_file']['full_path'];

            $contents = $this->get($path);
            $contents = str_replace($search, $replace, $contents);

            return $this->put($path, $contents);
        }

        public function saved(): array
        {
            return $this->saved;
        }
    });

    $this->addQueryToUrl = static function (string $url, array $query): string {
        $url = parse_url($url);

        $existingQuery = !isset($url['query']) ? [] : collect(explode('&', $url['query']))
            ->reduce(static function (array $acc, string $item): array {
                [$key, $value] = explode('=', $item);

                $acc[$key] = $value;

                return $acc;
            }, []);

        $query = [
            ...$query,
            ...$existingQuery,
        ];

        return "{$url['scheme']}://{$url['host']}{$url['path']}?" . http_build_query($query);
    };

    $this->defaultResponses = json_decode(
        file_get_contents($this->getFixtureDirectory('pull_stub_responses.json')),
        true,
    );
    $this->defaultResponsesWithFilters = fn (array $query) => (
        collect($this->defaultResponses)
            ->reduce(function (array $acc, array $value, string $url) use ($query): array {
                $url = $this->addQueryToUrl->__invoke($url, $query);

                $acc[$url] = $value;

                return $acc;
            }, [])
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

describe('base', function (): void {
    it('extends `AbstractCommandAction`', function (): void {
        expect(is_subclass_of(PullCommandAction::class, AbstractCommandAction::class))->toEqual(true);
    });

    it('executes for a given project', function (): void {
        Http::fake($this->defaultResponses);

        $action = (new PullCommandAction());

        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $action->execute($project, $branch);

        expect($action->project()->auth_key)->toEqual($project->auth_key);

        Http::assertSentCount(count($this->defaultResponses));
    });

    it('executes for a given branch', function (): void {
        Http::fake($this->defaultResponses);

        $action = (new PullCommandAction());

        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $action->execute($project, $branch);

        expect($action->branch()->name)->toEqual($branch->name);

        Http::assertSentCount(count($this->defaultResponses));
    });

    it('executes with a given conflict resolution', function (): void {
        Http::fake($this->defaultResponses);

        $action = (new PullCommandAction());

        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');
        $conflictResolution = BranchingConflictResolutionEnum::IGNORE;

        $action->execute($project, $branch, $conflictResolution);

        expect($action->conflictResolution())->toEqual($conflictResolution);

        Http::assertSentCount(count($this->defaultResponses));
    });

    it('executes with the default project conflict resolution by default', function (): void {
        Http::fake($this->defaultResponses);

        $action = (new PullCommandAction());

        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $action->execute($project, $branch);

        expect($action->conflictResolution())->toEqual($project->options->branching->conflict_resolution);

        Http::assertSentCount(count($this->defaultResponses));
    });

    it('pulles the translation sets from Transl', function (): void {
        Http::fake($this->defaultResponses);

        $action = (new PullCommandAction());

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch());

        expect(
            $translationSets
                ->map(static fn (TranslationSet $set): string => $set->trackingKey())
                ->sort()
                ->values()
                ->all(),
        )->toEqual(
            $this->pulledTranslationSets->__invoke()
                ->map(static fn (TranslationSet $set): string => $set->trackingKey())
                ->sort()
                ->values()
                ->all(),
        );
    });

    it('pulles the translation sets from Transl & applies the correct formatting', function (): void {
        Http::fake($this->defaultResponses);

        $action = (new PullCommandAction());

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        expect(app(Filesystem::class)->saved())->toMatchStandardizedSnapshot();
    });

    it('saves all locales when no filtering specified', function (): void {
        Http::fake($this->defaultResponses);

        $action = (new PullCommandAction());

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch());
        $locales = $translationSets->pluck('locale')->unique()->sort()->values()->all();
        $savedLocales = collect(app(Filesystem::class)->saved())
            ->pluck('path')
            ->map(fn (string $path): LangFilePath => LangFilePath::new(
                root: $this->getLangDirectory(),
                relativePath: str($path)->replace(DIRECTORY_SEPARATOR, '/')->after('/lang/')->value(),
            ))
            ->map(fn (LangFilePath $path): string => $path->guessLocale($this->getLangDirectory()))
            ->unique()
            ->sort()
            ->values()
            ->all();

        expect($locales)->toEqual($savedLocales);
        expect($savedLocales)->toEqual(['en', 'fr']);

        Http::assertSentCount(count($this->defaultResponses));
    });

    it('saves all groups when no filtering specified', function (): void {
        Http::fake($this->defaultResponses);

        $action = (new PullCommandAction());

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch());
        $groups = $translationSets->pluck('group')->unique()->sort()->values()->all();
        $savedGroups = collect(app(Filesystem::class)->saved())
            ->pluck('path')
            ->map(fn (string $path): LangFilePath => LangFilePath::new(
                root: $this->getLangDirectory(),
                relativePath: str($path)->replace(DIRECTORY_SEPARATOR, '/')->after('/lang/')->value(),
            ))
            ->map(fn (LangFilePath $path): ?string => $path->guessGroup($this->getLangDirectory()))
            ->unique()
            ->sort()
            ->values()
            ->all();

        expect($groups)->toEqual($savedGroups);
        expect($savedGroups)->toEqual(collect([
            'auth',
            'email',
            'flash',
            'value_types',
            'pages/dashboard/nav',
            'example',
            null,
        ])->sort()->values()->all());

        Http::assertSentCount(count($this->defaultResponses));
    });

    it('saves all namespaces when no filtering specified', function (): void {
        Http::fake($this->defaultResponses);

        $action = (new PullCommandAction());

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch());
        $namespaces = $translationSets->pluck('namespace')->unique()->sort()->values()->all();
        $savedNamespaces = collect(app(Filesystem::class)->saved())
            ->pluck('path')
            ->map(fn (string $path): LangFilePath => LangFilePath::new(
                root: $this->getLangDirectory(),
                relativePath: str($path)->replace(DIRECTORY_SEPARATOR, '/')->after('/lang/')->value(),
            ))
            ->map(fn (LangFilePath $path): ?string => $path->guessNamespace($this->getLangDirectory()))
            ->unique()
            ->sort()
            ->values()
            ->all();

        expect($namespaces)->toEqual($savedNamespaces);
        expect($savedNamespaces)->toEqual([null, 'some_package']);

        Http::assertSentCount(count($this->defaultResponses));
    });

    it('only saves the specified locales', function (): void {
        Http::fake($this->defaultResponsesWithFilters->__invoke(['only_locales' => 'fr']));

        $action = (new PullCommandAction())->acceptsLocales(['fr']);

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch())
            ->filter(static fn (TranslationSet $set): bool => $set->locale === 'fr');
        $locales = $translationSets->pluck('locale')->unique()->sort()->values()->all();
        $savedLocales = collect(app(Filesystem::class)->saved())
            ->pluck('path')
            ->map(fn (string $path): LangFilePath => LangFilePath::new(
                root: $this->getLangDirectory(),
                relativePath: str($path)->replace(DIRECTORY_SEPARATOR, '/')->after('/lang/')->value(),
            ))
            ->map(fn (LangFilePath $path): string => $path->guessLocale($this->getLangDirectory()))
            ->unique()
            ->sort()
            ->values()
            ->all();

        expect($locales)->toEqual($savedLocales);
        expect($savedLocales)->toEqual(['fr']);

        Http::assertSentCount(count($this->defaultResponsesWithFilters->__invoke([])));
    });

    it('only saves the specified groups', function (): void {
        Http::fake($this->defaultResponsesWithFilters->__invoke([
            'only_groups' => implode(',', ['auth', null, 'pages/dashboard/nav']),
        ]));

        $action = (new PullCommandAction())->acceptsGroups(['auth', null, 'pages/dashboard/nav']);

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch())
            ->filter(static fn (TranslationSet $set): bool => in_array($set->group, ['auth', null, 'pages/dashboard/nav'], true));
        $groups = $translationSets->pluck('group')->unique()->sort()->values()->all();
        $savedGroups = collect(app(Filesystem::class)->saved())
            ->pluck('path')
            ->map(fn (string $path): LangFilePath => LangFilePath::new(
                root: $this->getLangDirectory(),
                relativePath: str($path)->replace(DIRECTORY_SEPARATOR, '/')->after('/lang/')->value(),
            ))
            ->map(fn (LangFilePath $path): ?string => $path->guessGroup($this->getLangDirectory()))
            ->unique()
            ->sort()
            ->values()
            ->all();

        expect($groups)->toEqual($savedGroups);
        expect($savedGroups)->toEqual(collect([
            'auth',
            'pages/dashboard/nav',
            null,
        ])->sort()->values()->all());

        Http::assertSentCount(count($this->defaultResponsesWithFilters->__invoke([])));
    });

    it('only saves the specified namespaces', function (): void {
        Http::fake($this->defaultResponses);

        $action = (new PullCommandAction())->acceptsNamespaces([null]);

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch())
            ->filter(static fn (TranslationSet $set): bool => $set->namespace === null);
        $namespaces = $translationSets->pluck('namespace')->unique()->sort()->values()->all();
        $savedNamespaces = collect(app(Filesystem::class)->saved())
            ->pluck('path')
            ->map(fn (string $path): LangFilePath => LangFilePath::new(
                root: $this->getLangDirectory(),
                relativePath: str($path)->replace(DIRECTORY_SEPARATOR, '/')->after('/lang/')->value(),
            ))
            ->map(fn (LangFilePath $path): ?string => $path->guessNamespace($this->getLangDirectory()))
            ->unique()
            ->sort()
            ->values()
            ->all();

        expect($namespaces)->toEqual($savedNamespaces);
        expect($savedNamespaces)->toEqual([null]);

        Http::assertSentCount(count($this->defaultResponses));
    });

    it('rejects transfering the specified locales', function (): void {
        Http::fake($this->defaultResponsesWithFilters->__invoke(['except_locales' => 'fr']));

        $action = (new PullCommandAction())->rejectsLocales(['fr']);

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch())
            ->reject(static fn (TranslationSet $set): bool => $set->locale === 'fr');
        $locales = $translationSets->pluck('locale')->unique()->sort()->values()->all();
        $savedLocales = collect(app(Filesystem::class)->saved())
            ->pluck('path')
            ->map(fn (string $path): LangFilePath => LangFilePath::new(
                root: $this->getLangDirectory(),
                relativePath: str($path)->replace(DIRECTORY_SEPARATOR, '/')->after('/lang/')->value(),
            ))
            ->map(fn (LangFilePath $path): string => $path->guessLocale($this->getLangDirectory()))
            ->unique()
            ->sort()
            ->values()
            ->all();

        expect($locales)->toEqual($savedLocales);
        expect($savedLocales)->toEqual(['en']);

        Http::assertSentCount(count($this->defaultResponsesWithFilters->__invoke([])));
    });

    it('rejects transfering the specified groups', function (): void {
        Http::fake($this->defaultResponsesWithFilters->__invoke([
            'except_groups' => implode(',', ['auth', null, 'pages/dashboard/nav']),
        ]));

        $action = (new PullCommandAction())->rejectsGroups(['auth', null, 'pages/dashboard/nav']);

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch())
            ->reject(static fn (TranslationSet $set): bool => in_array($set->group, ['auth', null, 'pages/dashboard/nav'], true));
        $groups = $translationSets->pluck('group')->unique()->sort()->values()->all();
        $savedGroups = collect(app(Filesystem::class)->saved())
            ->pluck('path')
            ->map(fn (string $path): LangFilePath => LangFilePath::new(
                root: $this->getLangDirectory(),
                relativePath: str($path)->replace(DIRECTORY_SEPARATOR, '/')->after('/lang/')->value(),
            ))
            ->map(fn (LangFilePath $path): ?string => $path->guessGroup($this->getLangDirectory()))
            ->unique()
            ->sort()
            ->values()
            ->all();

        expect($groups)->toEqual($savedGroups);
        expect($savedGroups)->toEqual(collect([
            'email',
            'flash',
            'value_types',
            'example',
        ])->sort()->values()->all());

        Http::assertSentCount(count($this->defaultResponsesWithFilters->__invoke([])));
    });

    it('rejects transfering the specified namespaces', function (): void {
        Http::fake($this->defaultResponses);

        $action = (new PullCommandAction())->rejectsNamespaces([null]);

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch())
            ->reject(static fn (TranslationSet $set): bool => $set->namespace === null);
        $namespaces = $translationSets->pluck('namespace')->unique()->sort()->values()->all();
        $savedNamespaces = collect(app(Filesystem::class)->saved())
            ->pluck('path')
            ->map(fn (string $path): LangFilePath => LangFilePath::new(
                root: $this->getLangDirectory(),
                relativePath: str($path)->replace(DIRECTORY_SEPARATOR, '/')->after('/lang/')->value(),
            ))
            ->map(fn (LangFilePath $path): ?string => $path->guessNamespace($this->getLangDirectory()))
            ->unique()
            ->sort()
            ->values()
            ->all();

        expect($namespaces)->toEqual($savedNamespaces);
        expect($savedNamespaces)->toEqual(['some_package']);

        Http::assertSentCount(count($this->defaultResponses));
    });

    it('favors rejecting over accepting specified locales', function (): void {
        Http::fake($this->defaultResponsesWithFilters->__invoke([
            'only_locales' => 'fr',
            'except_locales' => 'fr',
        ]));

        $action = (new PullCommandAction())
            ->acceptsLocales(['fr'])
            ->rejectsLocales(['fr']);

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        expect(empty(app(Filesystem::class)->saved()))->toEqual(true);

        Http::assertSentCount(count($this->defaultResponsesWithFilters->__invoke([])));
    });

    it('favors rejecting over accepting specified groups', function (): void {
        Http::fake($this->defaultResponsesWithFilters->__invoke([
            'only_groups' => implode(',', ['auth', null, 'pages/dashboard/nav', 'example']),
            'except_groups' => implode(',', ['auth', null, 'pages/dashboard/nav']),
        ]));

        $action = (new PullCommandAction())
            ->acceptsGroups(['auth', null, 'pages/dashboard/nav', 'example'])
            ->rejectsGroups(['auth', null, 'pages/dashboard/nav']);

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch())
            ->filter(static fn (TranslationSet $set): bool => $set->group === 'example');
        $groups = $translationSets->pluck('group')->unique()->sort()->values()->all();
        $savedGroups = collect(app(Filesystem::class)->saved())
            ->pluck('path')
            ->map(fn (string $path): LangFilePath => LangFilePath::new(
                root: $this->getLangDirectory(),
                relativePath: str($path)->replace(DIRECTORY_SEPARATOR, '/')->after('/lang/')->value(),
            ))
            ->map(fn (LangFilePath $path): ?string => $path->guessGroup($this->getLangDirectory()))
            ->unique()
            ->sort()
            ->values()
            ->all();

        expect($groups)->toEqual($savedGroups);
        expect($savedGroups)->toEqual(['example']);

        Http::assertSentCount(count($this->defaultResponsesWithFilters->__invoke([])));
    });

    it('favors rejecting over accepting specified namespaces', function (): void {
        Http::fake($this->defaultResponses);

        $action = (new PullCommandAction())
            ->acceptsNamespaces([null])
            ->rejectsNamespaces([null]);

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        expect(empty(app(Filesystem::class)->saved()))->toEqual(true);

        Http::assertSentCount(count($this->defaultResponses));
    });

    it('notifies of translation sets skipped base on locales filters', function (): void {
        Http::fake($this->defaultResponsesWithFilters->__invoke(['except_locales' => 'fr']));

        $skipped = collect([]);

        $action = (new PullCommandAction())
            ->rejectsLocales(['fr'])
            ->onTranslationSetSkipped(static fn (TranslationSet $set) => $skipped->add($set));

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        expect($skipped->pluck('locale')->unique()->sort()->values()->all())->toEqual(['fr']);
    });

    it('notifies of translation sets skipped base on groups filters', function (): void {
        Http::fake($this->defaultResponsesWithFilters->__invoke([
            'except_groups' => implode(',', ['auth', null, 'pages/dashboard/nav']),
        ]));

        $skipped = collect([]);

        $action = (new PullCommandAction())
            ->rejectsGroups(['auth', null, 'pages/dashboard/nav'])
            ->onTranslationSetSkipped(static fn (TranslationSet $set) => $skipped->add($set));

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        expect($skipped->pluck('group')->unique()->sort()->values()->all())->toEqual(
            collect(['auth', null, 'pages/dashboard/nav'])->sort()->values()->all(),
        );
    });

    it('notifies of translation sets skipped base on namespaces filters', function (): void {
        Http::fake($this->defaultResponses);

        $skipped = collect([]);

        $action = (new PullCommandAction())
            ->rejectsNamespaces([null])
            ->onTranslationSetSkipped(static fn (TranslationSet $set) => $skipped->add($set));

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        expect($skipped->pluck('namespace')->unique()->sort()->values()->all())->toEqual([null]);
    });

    it('notifies of handled translation sets', function (): void {
        Http::fake($this->defaultResponsesWithFilters->__invoke(['only_locales' => 'fr']));

        $handled = collect([]);

        $action = (new PullCommandAction())
            ->acceptsLocales(['fr'])
            ->onTranslationSetHandled(static fn (TranslationSet $set) => $handled->add($set));

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        expect($handled->pluck('locale')->unique()->sort()->values()->all())->toEqual(['fr']);
    });

    it('sets the necessary HTTP headers & custom Transl HTTP headers', function (): void {
        Http::fake($this->defaultResponses);

        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        (new PullCommandAction())->execute($project, $branch);

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
});

describe('merge & conflict resolution', function (): void {
    it('notifies of incoming translation set conflicts', function (): void {
        Http::fake($this->defaultResponses);

        $conflicts = collect([]);

        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $action = (new PullCommandAction())->onIncomingTranslationSetConflicts(
            static fn (TranslationSet $set, TranslationLinesDiffing $diff) => $conflicts->add([$set, $diff]),
        );

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

        expect(static fn () => $action->execute($project, $branch))->toThrow(CouldNotResolveConflictWhilePulling::class);

        expect($conflicts->count())->toEqual(1);
        expect($conflicts[0][0]->trackingKey())->toEqual($conflictingSet->trackingKey());
        expect($conflicts[0][1]->conflictingLines()->toArray())->toEqual([$conflictingLine->toArray()]);
    });

    it('can silence conflict exceptions', function (BranchingConflictResolutionEnum $conflictResolution): void {
        Http::fake($this->defaultResponses);

        $conflicts = collect([]);

        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $action = (new PullCommandAction())
            ->silenceConflictExceptions()
            ->onIncomingTranslationSetConflicts(
                static fn (TranslationSet $set, TranslationLinesDiffing $diff) => $conflicts->add([$set, $diff]),
            );

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

        $action->execute($project, $branch, $conflictResolution);

        expect($conflicts->count())->toEqual(1);
        expect($conflicts[0][0]->trackingKey())->toEqual($conflictingSet->trackingKey());
        expect($conflicts[0][1]->conflictingLines()->toArray())->toEqual([$conflictingLine->toArray()]);
    })->with([BranchingConflictResolutionEnum::THROW, BranchingConflictResolutionEnum::MERGE_BUT_THROW]);

    it('can save previously untracked translation sets', function (): void {
        Http::fake($this->defaultResponses);

        $action = (new PullCommandAction());

        expect(empty(app(Filesystem::class)->saved()))->toEqual(true);

        $action->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

        expect(empty(app(Filesystem::class)->saved()))->toEqual(false);

        $translationSets = $this->getTranslationSets->__invoke($action->project(), $action->branch());

        expect(
            $translationSets
                ->map(static fn (TranslationSet $set): string => $set->meta['translation_file']['full_path'])
                ->sort()
                ->values()
                ->all(),
        )->toEqual(
            collect(app(Filesystem::class)->saved())
                ->map(static fn (array $item): string => $item['path'])
                ->sort()
                ->values()
                ->all(),
        );

        expect(app(Filesystem::class)->saved())->toMatchStandardizedSnapshot();
    });

    it('can save while accepting incoming translation sets', function (): void {
        $this->defaultResponses['https://api.transl.me/v0/commands/yolo/pull']['data'][0]['relations']['lines']['data'][] = [
            'id' => 'line_99',
            'type' => 'translation_line',
            'attributes' => [
                'id' => 'line_99',
                'key' => 'new_key',
                'value' => '[ADDED ON INCOMING]',
            ],
            'relations' => [],
            'meta' => null,
        ];

        $incomingSetTarget = $this->defaultResponses['https://api.transl.me/v0/commands/yolo/pull']['data'][0];

        Http::fake($this->defaultResponses);

        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $currentSets = $this->getTranslationSets->__invoke($project, $branch);

        foreach ($currentSets as $set) {
            $this->trackTranslationSet->__invoke($project, $branch, $set);
        }

        $conflictingSet = $currentSets->first(static function (TranslationSet $set) use ($incomingSetTarget): bool {
            return (
                $set->locale === $incomingSetTarget['attributes']['locale']
                && $set->group === $incomingSetTarget['attributes']['group']
                && $set->namespace === $incomingSetTarget['attributes']['namespace']
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

        (new PullCommandAction())->execute($project, $branch, BranchingConflictResolutionEnum::ACCEPT_INCOMING);

        expect(
            str_contains(file_get_contents($conflictingSet->meta['translation_file']['full_path']), '[UPDATE ON CURRENT]'),
        )->toEqual(false);

        expect(
            str_contains(file_get_contents($conflictingSet->meta['translation_file']['full_path']), '[ADDED ON INCOMING]'),
        )->toEqual(true);
    });

    it('can save while accepting current translation sets', function (): void {
        $this->defaultResponses['https://api.transl.me/v0/commands/yolo/pull']['data'][0]['relations']['lines']['data'][] = [
            'id' => 'line_99',
            'type' => 'translation_line',
            'attributes' => [
                'id' => 'line_99',
                'key' => 'new_key',
                'value' => '[ADDED ON INCOMING]',
            ],
            'meta' => null,
        ];

        $incomingSetTarget = $this->defaultResponses['https://api.transl.me/v0/commands/yolo/pull']['data'][0];

        Http::fake($this->defaultResponses);

        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $currentSets = $this->getTranslationSets->__invoke($project, $branch);

        foreach ($currentSets as $set) {
            $this->trackTranslationSet->__invoke($project, $branch, $set);
        }

        $conflictingSet = $currentSets->first(static function (TranslationSet $set) use ($incomingSetTarget): bool {
            return (
                $set->locale === $incomingSetTarget['attributes']['locale']
                && $set->group === $incomingSetTarget['attributes']['group']
                && $set->namespace === $incomingSetTarget['attributes']['namespace']
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

        (new PullCommandAction())->execute($project, $branch, BranchingConflictResolutionEnum::ACCEPT_CURRENT);

        expect(
            str_contains(file_get_contents($conflictingSet->meta['translation_file']['full_path']), '[UPDATE ON CURRENT]'),
        )->toEqual(true);

        expect(
            str_contains(file_get_contents($conflictingSet->meta['translation_file']['full_path']), '[ADDED ON INCOMING]'),
        )->toEqual(true);
    });

    it('can skip saving and throw on conflicts', function (): void {
        $this->defaultResponses['https://api.transl.me/v0/commands/yolo/pull']['data'][0]['relations']['lines'][] = [
            'id' => 'line_99',
            'type' => 'translation_line',
            'attributes' => [
                'id' => 'line_99',
                'key' => 'new_key',
                'value' => '[ADDED ON INCOMING]',
            ],
            'meta' => null,
        ];

        $incomingSetTarget = $this->defaultResponses['https://api.transl.me/v0/commands/yolo/pull']['data'][0];

        Http::fake($this->defaultResponses);

        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $currentSets = $this->getTranslationSets->__invoke($project, $branch);

        foreach ($currentSets as $set) {
            $this->trackTranslationSet->__invoke($project, $branch, $set);
        }

        $conflictingSet = $currentSets->first(static function (TranslationSet $set) use ($incomingSetTarget): bool {
            return (
                $set->locale === $incomingSetTarget['attributes']['locale']
                && $set->group === $incomingSetTarget['attributes']['group']
                && $set->namespace === $incomingSetTarget['attributes']['namespace']
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
            static fn () => (new PullCommandAction())->execute($project, $branch, BranchingConflictResolutionEnum::THROW),
        )->toThrow(CouldNotResolveConflictWhilePulling::class);

        expect(
            str_contains(file_get_contents($conflictingSet->meta['translation_file']['full_path']), '[UPDATE ON CURRENT]'),
        )->toEqual(true);

        expect(
            str_contains(file_get_contents($conflictingSet->meta['translation_file']['full_path']), '[ADDED ON INCOMING]'),
        )->toEqual(false);
    });

    it('can skip saving and ignore conflicts', function (): void {
        $this->defaultResponses['https://api.transl.me/v0/commands/yolo/pull']['data'][0]['relations']['lines'][] = [
            'id' => 'line_99',
            'type' => 'translation_line',
            'attributes' => [
                'id' => 'line_99',
                'key' => 'new_key',
                'value' => '[ADDED ON INCOMING]',
            ],
            'meta' => null,
        ];

        $incomingSetTarget = $this->defaultResponses['https://api.transl.me/v0/commands/yolo/pull']['data'][0];

        Http::fake($this->defaultResponses);

        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $currentSets = $this->getTranslationSets->__invoke($project, $branch);

        foreach ($currentSets as $set) {
            $this->trackTranslationSet->__invoke($project, $branch, $set);
        }

        $conflictingSet = $currentSets->first(static function (TranslationSet $set) use ($incomingSetTarget): bool {
            return (
                $set->locale === $incomingSetTarget['attributes']['locale']
                && $set->group === $incomingSetTarget['attributes']['group']
                && $set->namespace === $incomingSetTarget['attributes']['namespace']
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

        (new PullCommandAction())->execute($project, $branch, BranchingConflictResolutionEnum::IGNORE);

        expect(
            str_contains(file_get_contents($conflictingSet->meta['translation_file']['full_path']), '[UPDATE ON CURRENT]'),
        )->toEqual(true);

        expect(
            str_contains(file_get_contents($conflictingSet->meta['translation_file']['full_path']), '[ADDED ON INCOMING]'),
        )->toEqual(false);
    });

    it('can save while throwing on conflicts', function (): void {
        $this->defaultResponses['https://api.transl.me/v0/commands/yolo/pull']['data'][0]['relations']['lines']['data'][] = [
            'id' => 'line_99',
            'type' => 'translation_line',
            'attributes' => [
                'id' => 'line_99',
                'key' => 'new_key',
                'value' => '[ADDED ON INCOMING]',
            ],
            'meta' => null,
        ];

        $incomingSetTarget = $this->defaultResponses['https://api.transl.me/v0/commands/yolo/pull']['data'][0];

        Http::fake($this->defaultResponses);

        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $currentSets = $this->getTranslationSets->__invoke($project, $branch);

        foreach ($currentSets as $set) {
            $this->trackTranslationSet->__invoke($project, $branch, $set);
        }

        $conflictingSet = $currentSets->first(static function (TranslationSet $set) use ($incomingSetTarget): bool {
            return (
                $set->locale === $incomingSetTarget['attributes']['locale']
                && $set->group === $incomingSetTarget['attributes']['group']
                && $set->namespace === $incomingSetTarget['attributes']['namespace']
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
            static fn () => (new PullCommandAction())->execute($project, $branch, BranchingConflictResolutionEnum::MERGE_BUT_THROW),
        )->toThrow(CouldNotResolveConflictWhilePulling::class);

        expect(
            str_contains(file_get_contents($conflictingSet->meta['translation_file']['full_path']), '[UPDATE ON CURRENT]'),
        )->toEqual(true);

        expect(
            str_contains(file_get_contents($conflictingSet->meta['translation_file']['full_path']), '[ADDED ON INCOMING]'),
        )->toEqual(true);
    });

    it('can save while ignoring conflicts', function (): void {
        $this->defaultResponses['https://api.transl.me/v0/commands/yolo/pull']['data'][0]['relations']['lines']['data'][] = [
            'id' => 'line_99',
            'type' => 'translation_line',
            'attributes' => [
                'id' => 'line_99',
                'key' => 'new_key',
                'value' => '[ADDED ON INCOMING]',
            ],
            'meta' => null,
        ];

        $incomingSetTarget = $this->defaultResponses['https://api.transl.me/v0/commands/yolo/pull']['data'][0];

        Http::fake($this->defaultResponses);

        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $currentSets = $this->getTranslationSets->__invoke($project, $branch);

        foreach ($currentSets as $set) {
            $this->trackTranslationSet->__invoke($project, $branch, $set);
        }

        $conflictingSet = $currentSets->first(static function (TranslationSet $set) use ($incomingSetTarget): bool {
            return (
                $set->locale === $incomingSetTarget['attributes']['locale']
                && $set->group === $incomingSetTarget['attributes']['group']
                && $set->namespace === $incomingSetTarget['attributes']['namespace']
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

        (new PullCommandAction())->execute($project, $branch, BranchingConflictResolutionEnum::MERGE_AND_IGNORE);

        expect(
            str_contains(file_get_contents($conflictingSet->meta['translation_file']['full_path']), '[UPDATE ON CURRENT]'),
        )->toEqual(true);

        expect(
            str_contains(file_get_contents($conflictingSet->meta['translation_file']['full_path']), '[ADDED ON INCOMING]'),
        )->toEqual(true);
    });
});
