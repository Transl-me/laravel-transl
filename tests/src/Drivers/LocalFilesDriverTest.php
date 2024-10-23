<?php

declare(strict_types=1);

use Transl\Facades\Transl;
use Transl\Support\Branch;
use Transl\Support\TranslationSet;
use Transl\Drivers\LocalFilesDriver;
use Transl\Support\Contracts\Driverable;
use Illuminate\Contracts\Translation\Loader;
use Transl\Support\TranslationLineCollection;
use Transl\Exceptions\LocalFilesDriver\UnsupportedTranslationLoader;
use Transl\Actions\LocalFilesDriver\SaveTranslationSetToLocalFilesAction;
use Transl\Actions\LocalFilesDriver\GetTranslationSetsFromLocalFilesAction;
use Transl\Actions\LocalFilesDriver\TranslationContentsToTranslationSetAction;
use Transl\Actions\LocalFilesDriver\GetTranslationContentsFromLocalFilesAction;
use Transl\Actions\LocalFilesDriver\SaveTrackedTranslationSetToLocalFilesAction;
use Transl\Actions\LocalFilesDriver\GetTrackedTranslationSetFromLocalFilesAction;

it('implements the `Driverable` contract', function (): void {
    expect(new LocalFilesDriver() instanceof Driverable)->toEqual(true);
});

test('the "getTranslationContents" method uses the `GetTranslationContentsFromLocalFilesAction` action', function (): void {
    app()->bind(
        GetTranslationContentsFromLocalFilesAction::class,
        static fn () => new class () extends GetTranslationContentsFromLocalFilesAction {
            public function execute(string $locale, ?string $group, ?string $namespace): array
            {
                return compact('locale', 'group', 'namespace');
            }
        },
    );

    $result = (new LocalFilesDriver())->getTranslationContents(
        project: Transl::config()->projects()->first(),
        branch: Branch::asCurrent('test'),
        locale: 'en',
        group: 'auth',
        namespace: 'example',
    );

    expect($result)->toEqual([
        'locale' => 'en',
        'group' => 'auth',
        'namespace' => 'example',
    ]);
});

test('the "translationContentsToTranslationSet" method uses the `TranslationContentsToTranslationSetAction` action', function (): void {
    app()->bind(
        TranslationContentsToTranslationSetAction::class,
        static fn () => new class () extends TranslationContentsToTranslationSetAction {
            public function execute(array $contents, string $locale, ?string $group, ?string $namespace, ?array $meta): TranslationSet
            {
                return TranslationSet::new(
                    locale: "child-{$locale}",
                    group: "child-{$group}",
                    namespace: "child-{$namespace}",
                    lines: TranslationLineCollection::fromRawTranslationLines($contents),
                    meta: $meta,
                );
            }
        },
    );

    $result = (new LocalFilesDriver())->translationContentsToTranslationSet(
        project: Transl::config()->projects()->first(),
        branch: Branch::asCurrent('test'),
        contents: [],
        locale: 'en',
        group: 'auth',
        namespace: 'example',
        meta: null,
    );

    expect($result->toArray())->toEqual(
        TranslationSet::new(
            locale: 'child-en',
            group: 'child-auth',
            namespace: 'child-example',
            lines: TranslationLineCollection::make(),
            meta: null,
        )->toArray(),
    );
});

test('the "getTranslationSets" method uses the `GetTranslationSetsFromLocalFilesAction` action', function (): void {
    app()->bind(
        GetTranslationSetsFromLocalFilesAction::class,
        static fn () => new class () extends GetTranslationSetsFromLocalFilesAction {
            public function execute(): iterable
            {
                return ['stuff'];
            }
        },
    );

    $result = (new LocalFilesDriver())->getTranslationSets(
        project: Transl::config()->projects()->first(),
        branch: Branch::asCurrent('test'),
    );

    expect($result)->toEqual(['stuff']);
});

test('the "saveTranslationSet" method uses the `SaveTranslationSetToLocalFilesAction` action', function (): void {
    $instance = new class () extends SaveTranslationSetToLocalFilesAction {
        public bool $executed = false;

        public function execute(): void
        {
            $this->executed = true;
        }
    };

    app()->bind(
        SaveTranslationSetToLocalFilesAction::class,
        static fn () => $instance,
    );

    (new LocalFilesDriver())->saveTranslationSet(
        project: Transl::config()->projects()->first(),
        branch: Branch::asCurrent('test'),
        set: TranslationSet::new(
            locale: 'en',
            group: 'auth',
            namespace: 'example',
            lines: TranslationLineCollection::make(),
            meta: null,
        ),
    );

    expect($instance->executed)->toEqual(true);
});

test('the "getTrackedTranslationSet" method uses the `GetTrackedTranslationSetFromLocalFilesAction` action', function (): void {
    app()->bind(
        GetTrackedTranslationSetFromLocalFilesAction::class,
        static fn () => new class () extends GetTrackedTranslationSetFromLocalFilesAction {
            public function execute(TranslationSet $set): ?TranslationSet
            {
                return TranslationSet::new(
                    locale: "_child-{$set->locale}",
                    group: "_child-{$set->group}",
                    namespace: "_child-{$set->namespace}",
                    lines: TranslationLineCollection::make(),
                    meta: $set->meta,
                );
            }
        },
    );

    $set = TranslationSet::new(
        locale: 'en',
        group: 'auth',
        namespace: 'example',
        lines: TranslationLineCollection::make(),
        meta: null,
    );

    $result = (new LocalFilesDriver())->getTrackedTranslationSet(
        project: Transl::config()->projects()->first(),
        branch: Branch::asCurrent('test'),
        set: $set,
    );

    expect($result->toArray())->toEqual(
        TranslationSet::new(
            locale: "_child-{$set->locale}",
            group: "_child-{$set->group}",
            namespace: "_child-{$set->namespace}",
            lines: TranslationLineCollection::make(),
            meta: $set->meta,
        )->toArray(),
    );
});

test('the "saveTrackedTranslationSet" method uses the `SaveTrackedTranslationSetToLocalFilesAction` action', function (): void {
    $instance = new class () extends SaveTrackedTranslationSetToLocalFilesAction {
        public bool $executed = false;

        public function execute(TranslationSet $set): void
        {
            $this->executed = true;
        }
    };

    app()->bind(
        SaveTrackedTranslationSetToLocalFilesAction::class,
        static fn () => $instance,
    );

    (new LocalFilesDriver())->saveTrackedTranslationSet(
        project: Transl::config()->projects()->first(),
        branch: Branch::asCurrent('test'),
        set: TranslationSet::new(
            locale: 'en',
            group: 'auth',
            namespace: 'example',
            lines: TranslationLineCollection::make(),
            meta: null,
        ),
    );

    expect($instance->executed)->toEqual(true);
});

it('ensures only supported translation loaders are used', function (): void {
    app()->bind('translation.loader', static fn () => new class () implements Loader {
        public function load($locale, $group, $namespace = null)
        {
            return [];
        }

        public function addNamespace($namespace, $hint): void
        {
            //
        }

        public function namespaces()
        {
            return [];
        }

        public function addJsonPath($path): void
        {
            //
        }
    });

    expect(static fn () => (new LocalFilesDriver())->translationLoader())->toThrow(UnsupportedTranslationLoader::class);
});
