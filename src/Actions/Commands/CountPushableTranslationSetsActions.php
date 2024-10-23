<?php

declare(strict_types=1);

namespace Transl\Actions\Commands;

use Transl\Support\Branch;
use Transl\Config\ProjectConfiguration;
use Transl\Support\Contracts\Driverable;
use Transl\Actions\Commands\AbstractCommandAction;

class CountPushableTranslationSetsActions extends AbstractCommandAction
{
    /**
     * Count, accounting for the provided filters, the
     * amount of translation sets that will be handled.
     */
    public function execute(ProjectConfiguration $project, Branch $branch): int
    {
        $this->usingProject($project);
        $this->usingBranch($branch);

        $count = 0;

        foreach ($this->drivers() as $driverClass => $driverParams) {
            /** @var Driverable $driver */
            $driver = app($driverClass, $driverParams);

            $count = $count + $driver->countTranslationSets(
                $this->project(),
                $this->branch(),
                $this->passesFilterFactory(),
            );
        }

        return $count;
    }
}
