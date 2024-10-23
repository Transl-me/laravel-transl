<?php

declare(strict_types=1);

namespace Transl\Support;

use Transl\Support\Concerns\Instanciable;
use Transl\Actions\Commands\InitCommandAction;
use Transl\Actions\Commands\PullCommandAction;
use Transl\Actions\Commands\PushCommandAction;
use Transl\Actions\Commands\SynchCommandAction;

class Commands
{
    use Instanciable;

    public function pull(): PullCommandAction
    {
        return app(PullCommandAction::class);
    }

    public function push(): PushCommandAction
    {
        return app(PushCommandAction::class);
    }

    public function synch(): SynchCommandAction
    {
        return app(SynchCommandAction::class);
    }

    public function init(): InitCommandAction
    {
        return app(InitCommandAction::class);
    }
}
