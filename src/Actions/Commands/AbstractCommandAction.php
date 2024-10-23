<?php

declare(strict_types=1);

namespace Transl\Actions\Commands;

use Closure;
use Transl\Support\Branch;
use Transl\Support\TranslationSet;
use Transl\Config\ProjectConfiguration;
use Transl\Config\ProjectConfigurationDriverCollection;

abstract class AbstractCommandAction
{
    /**
     * The configured project to target.
     */
    protected ProjectConfiguration $project;

    /**
     * The branch on the defined project to target.
     */
    protected Branch $branch;

    /**
     * Will pull/push only translation lines from the specified locales.
     */
    protected array $onlyLocales = [];

    /**
     * Will pull/push only translation lines from the specified groups.
     */
    protected array $onlyGroups = [];

    /**
     * Will pull/push only translation lines from the specified namespaces.
     */
    protected array $onlyNamespaces = [];

    /**
     * Will pull/push only translation lines NOT from the specified locales.
     */
    protected array $exceptLocales = [];

    /**
     * Will pull/push only translation lines NOT from the specified groups.
     */
    protected array $exceptGroups = [];

    /**
     * Will pull/push only translation lines NOT from the specified namespaces.
     */
    protected array $exceptNamespaces = [];

    /**
     * A callback invoked when the target TranslationSet has been skipped.
     */
    protected ?Closure $translationSetSkippedCallback = null;

    /**
     * A callback invoked once the target TranslationSet has been handled.
     */
    protected ?Closure $translationSetHandledCallback = null;

    /* Hydration
    ------------------------------------------------*/

    public function acceptsLocales(array $values): static
    {
        $this->onlyLocales = $values;

        return $this;
    }

    public function acceptsGroups(array $values): static
    {
        $this->onlyGroups = $values;

        return $this;
    }

    public function acceptsNamespaces(array $values): static
    {
        $this->onlyNamespaces = $values;

        return $this;
    }

    public function rejectsLocales(array $values): static
    {
        $this->exceptLocales = $values;

        return $this;
    }

    public function rejectsGroups(array $values): static
    {
        $this->exceptGroups = $values;

        return $this;
    }

    public function rejectsNamespaces(array $values): static
    {
        $this->exceptNamespaces = $values;

        return $this;
    }

    /**
     * @param Closure(TranslationSet): void $callback
     */
    public function onTranslationSetSkipped(Closure $callback): static
    {
        $this->translationSetSkippedCallback = $callback;

        return $this;
    }

    /**
     * @param Closure(TranslationSet): void $callback
     */
    public function onTranslationSetHandled(Closure $callback): static
    {
        $this->translationSetHandledCallback = $callback;

        return $this;
    }

    /* Accessors
    ------------------------------------------------*/

    public function project(): ProjectConfiguration
    {
        return $this->project;
    }

    public function branch(): Branch
    {
        return $this->branch;
    }

    public function acceptedLocales(): array
    {
        return $this->onlyLocales;
    }

    public function acceptedGroups(): array
    {
        return $this->onlyGroups;
    }

    public function acceptedNamespaces(): array
    {
        return $this->onlyNamespaces;
    }

    public function rejectedLocales(): array
    {
        return $this->exceptLocales;
    }

    public function rejectedGroups(): array
    {
        return $this->exceptGroups;
    }

    public function rejectedNamespaces(): array
    {
        return $this->exceptNamespaces;
    }

    /* Hydration (bis)
    ------------------------------------------------*/

    protected function usingProject(ProjectConfiguration $project): static
    {
        $this->project = $project;

        return $this;
    }

    protected function usingBranch(Branch $branch): static
    {
        $this->branch = $branch;

        return $this;
    }

    /* Filters
    ------------------------------------------------*/

    protected function acceptsLocale(string $locale): bool
    {
        return $this->acceptsValue($locale, $this->acceptedLocales(), $this->rejectedLocales());
    }

    protected function acceptsGroup(?string $group): bool
    {
        return $this->acceptsValue($group, $this->acceptedGroups(), $this->rejectedGroups());
    }

    protected function acceptsNamespace(?string $namespace): bool
    {
        return $this->acceptsValue($namespace, $this->acceptedNamespaces(), $this->rejectedNamespaces());
    }

    protected function passesFilter(string $locale, ?string $group, ?string $namespace): bool
    {
        if (!$this->acceptsLocale($locale)) {
            return false;
        }

        if (!$this->acceptsGroup($group)) {
            return false;
        }

        if (!$this->acceptsNamespace($namespace)) {
            return false;
        }

        return true;
    }

    /**
     * @return callable(string $locale, ?string $group, ?string $namespace): bool
     */
    protected function passesFilterFactory(): callable
    {
        return function (string $locale, ?string $group, ?string $namespace): bool {
            return $this->passesFilter($locale, $group, $namespace);
        };
    }

    /* Actions
    ------------------------------------------------*/

    protected function drivers(): ProjectConfigurationDriverCollection
    {
        return $this->project()->drivers;
    }

    protected function invokeTranslationSetSkippedCallback(TranslationSet $set): void
    {
        if (!$this->translationSetSkippedCallback) {
            return;
        }

        ($this->translationSetSkippedCallback)($set);
    }

    protected function invokeTranslationSetHandledCallback(TranslationSet $set): void
    {
        if (!$this->translationSetHandledCallback) {
            return;
        }

        ($this->translationSetHandledCallback)($set);
    }

    /* Helpers
    ------------------------------------------------*/

    protected function acceptsValue(?string $value, array $onlyList, array $exceptList): bool
    {
        if (!empty($exceptList) && in_array($value, $exceptList, true)) {
            return false;
        }

        if (!empty($onlyList) && !in_array($value, $onlyList, true)) {
            return false;
        }

        return true;
    }
}
