<?php

declare(strict_types=1);

namespace Transl\Actions\LocalFilesDriver;

use Transl\Support\Branch;
use Transl\Drivers\LocalFilesDriver;
use Transl\Config\ProjectConfiguration;

abstract class AbstractLocalFilesDriverAction
{
    protected LocalFilesDriver $driver;
    protected ProjectConfiguration $project;
    protected Branch $branch;
    protected array $languageDirectories;
    protected bool $ignorePackageTranslations;
    protected bool $ignoreVendorTranslations;

    public function usingDriver(LocalFilesDriver $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    public function usingProject(ProjectConfiguration $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function usingBranch(Branch $branch): static
    {
        $this->branch = $branch;

        return $this;
    }

    public function usingLanguageDirectories(array $languageDirectories): static
    {
        $this->languageDirectories = $languageDirectories;

        return $this;
    }

    public function shouldIgnorePackageTranslations(bool $ignorePackageTranslations): static
    {
        $this->ignorePackageTranslations = $ignorePackageTranslations;

        return $this;
    }

    public function shouldIgnoreVendorTranslations(bool $ignoreVendorTranslations): static
    {
        $this->ignoreVendorTranslations = $ignoreVendorTranslations;

        return $this;
    }
}
