<?php

declare(strict_types=1);

use Transl\Facades\Transl;
use Transl\Support\Branch;
use Transl\Support\TranslationSet;
use Illuminate\Support\Facades\File;
use Transl\Drivers\LocalFilesDriver;
use Illuminate\Filesystem\Filesystem;
use Transl\Config\ProjectConfiguration;
use Symfony\Component\Finder\SplFileInfo;
use Transl\Support\TranslationLineCollection;
use Transl\Actions\LocalFilesDriver\SaveTranslationSetToLocalFilesAction;
use Transl\Actions\LocalFilesDriver\GetTranslationSetsFromLocalFilesAction;
use Transl\Exceptions\LocalFilesDriver\CouldNotDetermineTranslationFileRelativePathFromTranslationSet;

beforeEach(function (): void {
    File::swap(new class () extends Filesystem {
        protected array $saved = [];

        public function ensureDirectoryExists($path, $mode = 0755, $recursive = true): void
        {
            //
        }

        public function put($path, $contents, $lock = false)
        {
            $this->saved[] = [
                'path' => $path,
                'contents' => $contents,
                'lock' => $lock,
            ];

            // return parent::put($path, $contents, $lock);

            return 123;
        }

        public function saved(): array
        {
            return $this->saved;
        }
    });
});

it('works', function (): void {
    $files = collect(app(Filesystem::class)->allFiles($this->getLangDirectory()));

    $sets = (new GetTranslationSetsFromLocalFilesAction())
        ->usingProject(Transl::config()->projects()->first())
        ->usingBranch(Branch::asCurrent('test'))
        ->usingDriver(new LocalFilesDriver())
        ->usingLanguageDirectories([$this->getLangDirectory()])
        ->shouldIgnorePackageTranslations(false)
        ->shouldIgnoreVendorTranslations(false)
        ->execute();

    foreach ($sets as $set) {
        (new SaveTranslationSetToLocalFilesAction())
            ->usingProject(Transl::config()->projects()->first())
            ->usingBranch(Branch::asCurrent('test'))
            ->usingDriver(new LocalFilesDriver())
            ->usingLanguageDirectories([$this->getLangDirectory()])
            ->usingTranslationSet($set)
            ->execute();
    }

    /** @var array $saved */
    $saved = app(Filesystem::class)->saved();
    $savedPaths = collect($saved)->pluck('path')->map(static function (string $path): string {
        return str_replace(DIRECTORY_SEPARATOR, '/', $path);
    });

    $files = $files->filter(static function (SplFileInfo $file) use ($savedPaths): bool {
        $fullPath = str_replace(DIRECTORY_SEPARATOR, '/', $file->getRealPath());

        return !$savedPaths->contains($fullPath);
    });

    expect($files->isEmpty())->toEqual(true);

    expect(app(Filesystem::class)->saved())->toMatchStandardizedSnapshot();
});

it("can default to the first defined default language directories", function (): void {
    $set = TranslationSet::new(
        locale: 'ht',
        group: null,
        namespace: null,
        lines: TranslationLineCollection::make(),
        meta: null,
    );

    $driver = new class () extends LocalFilesDriver {
        public function defaultLanguageDirectories(ProjectConfiguration $project, Branch $branch): array
        {
            return [base_path('should_be_choosen'), base_path('nope')];
        }
    };

    $instance = (new SaveTranslationSetToLocalFilesAction())
        ->usingProject(Transl::config()->projects()->first())
        ->usingBranch(Branch::asCurrent('test'))
        ->usingDriver($driver)
        ->usingLanguageDirectories([])
        ->usingTranslationSet($set);

    $instance->execute();

    $savedPaths = collect(app(Filesystem::class)->saved())->pluck('path')->map(static function (string $path): string {
        return str_replace(DIRECTORY_SEPARATOR, '/', $path);
    });

    expect($savedPaths->count())->toEqual(1);
    expect($savedPaths->first())->toEqual($this->getTestSupportDirectory('should_be_choosen/ht.json'));
});

it("throws an exception when it cannot determine the translation file's relative path", function (): void {
    $set = TranslationSet::new(
        locale: 'ht',
        group: null,
        namespace: 'yolo',
        lines: TranslationLineCollection::make(),
        meta: null,
    );

    $instance = (new SaveTranslationSetToLocalFilesAction())
        ->usingProject(Transl::config()->projects()->first())
        ->usingBranch(Branch::asCurrent('test'))
        ->usingDriver(new LocalFilesDriver())
        ->usingLanguageDirectories([$this->getLangDirectory()])
        ->usingTranslationSet($set);

    expect(static fn () => $instance->execute())->toThrow(CouldNotDetermineTranslationFileRelativePathFromTranslationSet::class);
});
