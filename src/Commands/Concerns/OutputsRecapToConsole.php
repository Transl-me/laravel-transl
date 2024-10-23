<?php

declare(strict_types=1);

namespace Transl\Commands\Concerns;

/**
 * @mixin \Illuminate\Console\Command
 */
trait OutputsRecapToConsole
{
    protected function outputRecap(): void
    {
        $this->beforeRecap();

        $this->components->bulletList(
            collect($this->buildRecapData($this->recapExtraData()))
                ->filter()
                ->map(static fn (string $value, string $key): string => "{$key}: {$value}")
                ->all(),
        );
    }

    protected function beforeRecap(): void
    {
        //
    }

    protected function recapExtraData(): array
    {
        return [];
    }

    protected function buildRecapData(array $extra = []): array
    {
        return [
            'Project' => $this->buildProjectRecapValue(),
            'Branch' => $this->buildBranchRecapValue(),
            ...$extra,
            'Only locales' => $this->buildOnlyLocalesRecapValue(),
            'Only groups' => $this->buildOnlyGroupsRecapValue(),
            'Only namespaces' => $this->buildOnlyNamespacesRecapValue(),
            'Except locales' => $this->buildExceptLocalesRecapValue(),
            'Except groups' => $this->buildExceptGroupsRecapValue(),
            'Except namespaces' => $this->buildExceptNamespacesRecapValue(),
        ];
    }

    protected function buildProjectRecapValue(): ?string
    {
        if (!property_exists($this, 'project')) {
            return null;
        }

        return $this->project->label();
    }

    protected function buildBranchRecapValue(): ?string
    {
        if (!property_exists($this, 'branch')) {
            return null;
        }

        $provenance = $this->branch->provenance();

        return $this->branch->name . ($provenance ? " ({$provenance})" : '');
    }

    protected function buildOnlyLocalesRecapValue(): ?string
    {
        if (!property_exists($this, 'onlyLocales')) {
            return null;
        }

        return implode(', ', $this->onlyLocales);
    }

    protected function buildOnlyGroupsRecapValue(): ?string
    {
        if (!property_exists($this, 'onlyGroups')) {
            return null;
        }

        return implode(', ', $this->onlyGroups);
    }

    protected function buildOnlyNamespacesRecapValue(): ?string
    {
        if (!property_exists($this, 'onlyNamespaces')) {
            return null;
        }

        return implode(', ', $this->onlyNamespaces);
    }

    protected function buildExceptLocalesRecapValue(): ?string
    {
        if (!property_exists($this, 'exceptLocales')) {
            return null;
        }

        return implode(', ', $this->exceptLocales);
    }

    protected function buildExceptGroupsRecapValue(): ?string
    {
        if (!property_exists($this, 'exceptGroups')) {
            return null;
        }

        return implode(', ', $this->exceptGroups);
    }

    protected function buildExceptNamespacesRecapValue(): ?string
    {
        if (!property_exists($this, 'exceptNamespaces')) {
            return null;
        }

        return implode(', ', $this->exceptNamespaces);
    }
}
