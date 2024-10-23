<?php

declare(strict_types=1);

namespace Transl\Commands\Concerns;

/**
 * @mixin \Illuminate\Console\Command
 */
trait FiltersTranslationSet
{
    /**
     * Will pull/push only translation lines from the specified locales.
     */
    protected array $onlyLocales;

    /**
     * Will pull/push only translation lines from the specified groups.
     */
    protected array $onlyGroups;

    /**
     * Will pull/push only translation lines from the specified namespaces.
     */
    protected array $onlyNamespaces;

    /**
     * Will pull/push only translation lines NOT from the specified locales.
     */
    protected array $exceptLocales;

    /**
     * Will pull/push only translation lines NOT from the specified groups.
     */
    protected array $exceptGroups;

    /**
     * Will pull/push only translation lines NOT from the specified namespaces.
     */
    protected array $exceptNamespaces;

    /* Methods
    ------------------------------------------------*/

    protected function hydrateOnlyLocalesProperty(?array $values): void
    {
        $this->onlyLocales = $this->arrayableOptionValues($values);
    }

    protected function hydrateOnlyGroupsProperty(?array $values): void
    {
        $this->onlyGroups = $this->arrayableOptionValues($values);
    }

    protected function hydrateOnlyNamespacesProperty(?array $values): void
    {
        $this->onlyNamespaces = $this->arrayableOptionValues($values);
    }

    protected function hydrateExceptLocalesProperty(?array $values): void
    {
        $this->exceptLocales = $this->arrayableOptionValues($values);
    }

    protected function hydrateExceptGroupsProperty(?array $values): void
    {
        $this->exceptGroups = $this->arrayableOptionValues($values);
    }

    protected function hydrateExceptNamespacesProperty(?array $values): void
    {
        $this->exceptNamespaces = $this->arrayableOptionValues($values);
    }
}
