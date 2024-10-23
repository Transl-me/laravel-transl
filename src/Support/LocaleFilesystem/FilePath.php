<?php

declare(strict_types=1);

namespace Transl\Support\LocaleFilesystem;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, string>
 * @phpstan-consistent-constructor
 */
class FilePath implements Arrayable
{
    protected string $root;
    protected string $relativePath;
    protected string $directorySeparator;

    public function __construct(string $root, string $relativePath = '', string $directorySeparator = DIRECTORY_SEPARATOR)
    {
        $this->root = $root;
        $this->relativePath = $relativePath;
        $this->directorySeparator = $directorySeparator;

        $this->standardizePaths();
    }

    public static function new(string $root, string $relativePath = '', string $directorySeparator = DIRECTORY_SEPARATOR): static
    {
        return new static($root, $relativePath, $directorySeparator);
    }

    public static function wrap(string|FilePath $path): static
    {
        if (is_string($path)) {
            $path = static::new($path);
        }

        /** @var static $path */
        return $path;
    }

    public function withDirectorySeparator(string $directorySeparator): static
    {
        $this->directorySeparator = $directorySeparator;

        $this->standardizePaths();

        return $this;
    }

    public function root(): string
    {
        return $this->root;
    }

    public function relativePath(): string
    {
        return $this->relativePath;
    }

    public function directorySeparator(): string
    {
        return $this->directorySeparator;
    }

    public function fullPath(): string
    {
        return $this->standardizePathTrailingDirectorySeparator(
            "{$this->root()}{$this->directorySeparator()}{$this->relativePath()}",
            $this->directorySeparator(),
        );
    }

    public function directoryName(): string
    {
        return pathinfo(pathinfo($this->fullPath(), PATHINFO_DIRNAME), PATHINFO_FILENAME);
    }

    public function fileName(): string
    {
        return pathinfo($this->fullPath(), PATHINFO_BASENAME);
    }

    public function fileNameWithoutExtension(): string
    {
        return pathinfo($this->fullPath(), PATHINFO_FILENAME);
    }

    public function extension(): string
    {
        return pathinfo($this->fullPath(), PATHINFO_EXTENSION);
    }

    public function append(string $path): static
    {
        return static::new(
            $this->fullPath(),
            $path,
            $this->directorySeparator(),
        );
    }

    public function relativeFrom(string|FilePath $root): string
    {
        if ($root instanceof FilePath) {
            $root = $root->fullPath();
        }

        $root = $this->standardizePathDirectorySeparator($root, $this->directorySeparator());
        $root = $this->standardizePathTrailingDirectorySeparator($root, $this->directorySeparator());

        $fullPath = $this->fullPath();

        $value = str_replace($root, '', $fullPath);

        if ($value === $fullPath) {
            return $value;
        }

        return $this->standardizePathLeadingAndTrailingDirectorySeparators($value, $this->directorySeparator());
    }

    public function exists(): bool
    {
        return file_exists($this->fullPath());
    }

    public function isDirectory(): bool
    {
        return is_dir($this->fullPath());
    }

    public function isFile(): bool
    {
        return is_file($this->fullPath());
    }

    public function isNestedWithin(string $root): bool
    {
        $root = $this->standardizePathDirectorySeparator($root, $this->directorySeparator());
        $fullPath = $this->fullPath();

        return ($fullPath !== $root) && str_starts_with($fullPath, $root);
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'root' => $this->root(),
            'relative_path' => $this->relativePath(),
            'directory_separator' => $this->directorySeparator(),
            'full_path' => $this->fullPath(),
        ];
    }

    protected function standardizePaths(): void
    {
        $this->root = $this->standardizePathDirectorySeparator($this->root, $this->directorySeparator());
        $this->relativePath = $this->standardizePathDirectorySeparator($this->relativePath, $this->directorySeparator());

        $this->root = $this->standardizePathTrailingDirectorySeparator($this->root, $this->directorySeparator());
        $this->relativePath = $this->standardizePathLeadingAndTrailingDirectorySeparators($this->relativePath, $this->directorySeparator());
    }

    protected function standardizePathDirectorySeparator(string $path, string $directorySeparator): string
    {
        return str_replace(['\\', '\\\\', '/', '//'], $directorySeparator, $path);
    }

    protected function standardizePathTrailingDirectorySeparator(string $path, string $directorySeparator): string
    {
        return rtrim($path, $directorySeparator);
    }

    protected function standardizePathLeadingAndTrailingDirectorySeparators(string $path, string $directorySeparator): string
    {
        return trim($path, $directorySeparator);
    }
}
