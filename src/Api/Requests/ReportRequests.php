<?php

declare(strict_types=1);

namespace Transl\Api\Requests;

use Transl\Api\Client;
use Transl\Support\Branch;
use Transl\Config\ProjectConfiguration;
use Transl\Support\Concerns\Instanciable;
use Transl\Support\Reports\MissingTranslationKeys\MissingTranslationKey;

class ReportRequests
{
    use Instanciable;

    /**
     * @param array<array-key, MissingTranslationKey> $keys
     */
    public function missingTranslationKey(ProjectConfiguration $project, Branch $branch, array $keys): void
    {
        Client::new()
            ->withProject($project)
            ->withBranch($branch)
            ->http()
            ->post('/reports/missing-translation-keys', ['keys' => $keys]);
    }
}
