<?php

declare(strict_types=1);

namespace Transl\Tests\TestSupport\app\Helpers;

use Transl\Support\TranslationSet;

class TranslationSetHelper
{
    public static function new(): static
    {
        return new static();
    }

    public function determineTranslationFileFullPath(...$args): ?string
    {
        if (count($args) === 1) {
            /** @var TranslationSet $set */
            $set = $args[0];

            $args = [
                'locale' => $set->locale,
                'group' => $set->group,
                'namespace' => $set->namespace,
            ];
        }

        return $this->tryMakeTranslationFileFullPath(...$args);
    }

    public function determineTranslationFileRelativePath(...$args): ?string
    {
        if (count($args) === 1) {
            /** @var TranslationSet $set */
            $set = $args[0];

            $args = [
                'locale' => $set->locale,
                'group' => $set->group,
                'namespace' => $set->namespace,
            ];
        }

        return $this->tryMakeTranslationFileRelativePath(...$args);
    }

    protected function tryMakeTranslationFileFullPath(string $locale, ?string $group, ?string $namespace): ?string
    {
        $path = $this->tryMakeTranslationFileRelativePath($locale, $group, $namespace);

        if (!$path) {
            return null;
        }

        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, lang_path($path));
    }

    protected function tryMakeTranslationFileRelativePath(string $locale, ?string $group, ?string $namespace): ?string
    {
        $translationFileRelativePath = null;

        if ($namespace && $group) {
            $translationFileRelativePath = "vendor/{$namespace}/{$locale}/{$group}.php";
        }

        if (!$namespace && $group) {
            $translationFileRelativePath = "{$locale}/{$group}.php";
        }

        if (!$namespace && !$group) {
            $translationFileRelativePath = "{$locale}.json";
        }

        return $translationFileRelativePath;
    }
}
