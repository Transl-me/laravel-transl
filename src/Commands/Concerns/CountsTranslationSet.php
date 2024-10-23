<?php

declare(strict_types=1);

namespace Transl\Commands\Concerns;

use Transl\Actions\Commands\CountPushableTranslationSetsActions;

/**
 * @mixin \Illuminate\Console\Command
 */
trait CountsTranslationSet
{
    protected function count(): int
    {
        return app(CountPushableTranslationSetsActions::class)
            ->acceptsLocales($this->onlyLocales)
            ->acceptsGroups($this->onlyGroups)
            ->acceptsNamespaces($this->onlyNamespaces)
            ->rejectsLocales($this->exceptLocales)
            ->rejectsGroups($this->exceptGroups)
            ->rejectsNamespaces($this->exceptNamespaces)
            ->execute($this->project, $this->branch);
    }
}
