<?php

declare(strict_types=1);

namespace Transl\Support;

use Transl\Api\Requests\ReportRequests;
use Transl\Api\Requests\CommandRequests;
use Transl\Support\Concerns\Instanciable;

class Api
{
    use Instanciable;

    public function commands(): CommandRequests
    {
        return CommandRequests::instance();
    }

    public function reports(): ReportRequests
    {
        return ReportRequests::instance();
    }
}
