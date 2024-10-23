<?php

declare(strict_types=1);

namespace Transl\Support\LocaleFilesystem;

use Transl\Support\Helper;
use Transl\Support\LocaleFilesystem\FilePath;

class LangFilePath extends FilePath
{
    public function relativeFromPackage(string|FilePath $languageDirectory, ?string $packageName = null): string
    {
        $languageDirectory = FilePath::wrap($languageDirectory);

        if ($packageName) {
            return $this->relativeFrom($languageDirectory->append("vendor/{$packageName}"));
        }

        $path = $this->relativeFrom($languageDirectory->append('vendor'));

        return $this->afterFirstSegmentInPath($path, $this->directorySeparator());
    }

    public function guessLocale(string|FilePath $languageDirectory): string
    {
        if ($this->isJson()) {
            return $this->fileNameWithoutExtension();
        }

        return $this->getFirstSegmentInPath(
            $this->relativeFromBaseOrPackage($languageDirectory),
            $this->directorySeparator(),
        );
    }

    public function guessGroup(string|FilePath $languageDirectory): ?string
    {
        if ($this->isJson()) {
            return null;
        }

        $path = $this->afterFirstSegmentInPath(
            $this->relativeFromBaseOrPackage($languageDirectory),
            $this->directorySeparator(),
        );

        /**
         * Example:
         * - /en/auth.php -> auth
         * - /en/pages/dashboard/nav.php -> pages/dashboard/nav
         *
         * Note: https://github.com/laravel/docs/pull/3957
         * > Laravel localization doesn't follow the usual "dot notation" for files in nested directories.
         * > This behavior is not intentional and thus may not be supported in the future so would rather not document it.
         */
        return str_replace([".{$this->extension()}", $this->directorySeparator()], ['', '/'], $path);
    }

    public function guessNamespace(string|FilePath $languageDirectory): ?string
    {
        if ($this->isJson()) {
            return null;
        }

        if (!$this->isPackage()) {
            return null;
        }

        $languageDirectory = FilePath::wrap($languageDirectory);

        return $this->getFirstSegmentInPath(
            $this->relativeFrom($languageDirectory->append('vendor')),
            $this->directorySeparator(),
        );
    }

    public function isJson(): bool
    {
        return $this->extension() === 'json';
    }

    public function isPhp(): bool
    {
        return $this->extension() === 'php';
    }

    public function isPackage(): bool
    {
        return str_starts_with($this->relativePath(), 'vendor' . $this->directorySeparator());
    }

    public function inVendor(): bool
    {
        return str_contains($this->root(), $this->directorySeparator() . 'vendor');
    }

    protected function relativeFromBaseOrPackage(string|FilePath $path): string
    {
        return $this->isPackage() ? $this->relativeFromPackage($path) : $this->relativeFrom($path);
    }

    protected function afterFirstSegmentInPath(string $path, string $directorySeparator): string
    {
        return mb_substr($path, Helper::strpos($path, $directorySeparator) + 1);
    }

    protected function getFirstSegmentInPath(string $path, string $directorySeparator): string
    {
        return mb_substr($path, 0, Helper::strpos($path, $directorySeparator));
    }
}
