<?php

declare(strict_types=1);

namespace Transl\Actions\Reports;

use Transl\Facades\Transl;
use Transl\Support\Branch;
use Illuminate\Support\Collection;
use Transl\Config\ProjectConfiguration;
use Transl\Support\Reports\MissingTranslationKeys\MissingTranslationKey;
use Transl\Support\Reports\MissingTranslationKeys\MissingTranslationKeyReport;

class SendMissingTranslationKeyReportAction
{
    /**
     * Execute the action.
     *
     * @param array<array-key, MissingTranslationKeyReport> $reports
     */
    public function execute(array $reports): void
    {
        $this->reportGroups($this->groupReports($reports));
    }

    /* Actions
    ------------------------------------------------*/

    /**
     * @param array<array-key, MissingTranslationKeyReport> $reports
     * @return Collection<string, Collection<int, MissingTranslationKeyReport>>
     */
    protected function groupReports(array $reports): Collection
    {
        return collect($reports)->groupBy(static fn (MissingTranslationKeyReport $report): string => $report->group());
    }

    /**
     * @param Collection<string, Collection<int, MissingTranslationKeyReport>> $group
     */
    protected function reportGroups(Collection $group): void
    {
        foreach ($group as $reports) {
            /** @var MissingTranslationKeyReport $report */
            $report = $reports->first();

            $project = $report->project;
            $branch = $report->branch;

            $keys = $reports->reduce(static function (array $acc, MissingTranslationKeyReport $report): array {
                $acc[$report->key->id()] = $report->key;

                return $acc;
            }, []);

            $this->reportToTransl($project, $branch, $keys);
        }
    }

    /**
     * @param array<array-key, MissingTranslationKey> $keys
     */
    protected function reportToTransl(ProjectConfiguration $project, Branch $branch, array $keys): void
    {
        Transl::api()->reports()->missingTranslationKey($project, $branch, $keys);
    }
}
