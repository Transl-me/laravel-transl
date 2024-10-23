<?php

declare(strict_types=1);

namespace Transl\Support\Reports;

use Transl\Support\Concerns\Instanciable;
use Transl\Support\Reports\MissingTranslationKeys\MissingTranslationKeys;

class Reports
{
    use Instanciable;

    public function missingTranslationKeys(): MissingTranslationKeys
    {
        return app(MissingTranslationKeys::class);
    }
}
