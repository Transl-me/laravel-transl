<?php

declare(strict_types=1);

namespace Transl\Commands\Concerns;

use Transl\Facades\Transl;
use Transl\Config\Configuration;

/**
 * @mixin \Illuminate\Console\Command
 */
trait UsesConfig
{
    /**
     * The Transl configuration.
     */
    protected Configuration $config;

    /* Methods
    ------------------------------------------------*/

    protected function hydrateConfigProperty(): void
    {
        $this->config = Transl::config();
    }
}
