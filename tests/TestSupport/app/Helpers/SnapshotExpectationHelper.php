<?php

declare(strict_types=1);

namespace Transl\Tests\TestSupport\app\Helpers;

use Pest\Expectation;
use Transl\Support\TranslationSet;

class SnapshotExpectationHelper
{
    public function __construct(protected Expectation $expectation)
    {
    }

    public static function new(Expectation $expectation): static
    {
        return new static($expectation);
    }

    public function handle(): mixed
    {
        $value = $this->expectation->value;

        if (is_array($value) && isset($value[0]) && is_array($value[0]) && isset($value[0]['path'])) {
            return $this->handleFilesystemPutItems($value);
        }

        if (is_array($value) && isset($value[0]) && $value[0] instanceof TranslationSet) {
            return $this->handleTranslationSets($value);
        }

        return $value;
    }

    /**
     * Catches snapshots of:
     * - `tests/src/Actions/LocalFilesDriver/SaveTranslationSetToLocalFilesActionTest.php`
     * - `tests/src/Actions/Commands/PullCommandActionTest.php`
     * - probably others
     */
    protected function handleFilesystemPutItems(array $items): array
    {
        return collect($items)
            ->map(function (array $item): array {
                $item['path'] = $this->standardizePath($item['path']);
                $item['contents'] = str_replace(["\r\n", "\r"], "\n", $item['contents']);

                return $item;
            })
            ->sortBy(static function (array $item): string {
                return $item['path'];
            })
            ->values()
            ->all();
    }

    /**
     * Catches snapshots of:
     * - `tests/src/Actions/LocalFilesDriver/GetTranslationSetsFromLocalFilesActionTest.php`
     * - probably others
     */
    protected function handleTranslationSets(array $items): array
    {
        return $items;
        // return collect($items)
        //     ->map(function (TranslationSet $item): TranslationSet {
        //         $meta = $item->meta;

        //         if (isset($meta['language_directory'])) {
        //             $meta['language_directory'] = $this->standardizePaths($meta['language_directory']);
        //         }

        //         if (isset($meta['translation_file'])) {
        //             $meta['translation_file'] = $this->standardizePaths($meta['translation_file']);
        //         }

        //         return TranslationSet::from([
        //             ...$item->toArray(),
        //             'meta' => $meta,
        //         ]);
        //     })
        //     ->sortBy(static function (TranslationSet $item): string {
        //         return $item->trackingKey();
        //     })
        //     ->values()
        //     ->all();
    }

    protected function standardizePath(string $path): string
    {
        $root = str_replace(DIRECTORY_SEPARATOR, '/', dirname(__FILE__, 4));

        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

        return str_replace($root, '/transl-me/laravel-transl', $path);
    }

    protected function standardizePaths(array $paths): array
    {
        return collect($paths)->map(function (string $path): string {
            return $this->standardizePath($path);
        })->all();
    }
}
