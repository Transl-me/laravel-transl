<?php

declare(strict_types=1);

namespace Transl\Commands\Concerns;

use Transl\Config\ProjectConfiguration;

/**
 * @mixin \Illuminate\Console\Command
 */
trait UsesProject
{
    /**
     * The configured project to target.
     */
    protected ProjectConfiguration $project;

    /* Methods
    ------------------------------------------------*/

    protected function hydrateProjectProperty(?string $value): void
    {
        $project = $value ? trim($value) : $this->config->defaults()->project;
        $projects = null;

        if ($project) {
            $projects = $this->config->projects()->whereAuthKeyOrName($project);
        }

        if (!$projects || $projects->isEmpty()) {
            $this->throw(
                "No target project could be determined. Please either provide the `--project=my_project_auth_key_or_name` option or the `transl.defaults.project` config value with the `auth_key` of an existing project on Transl.me.",
            );
        }

        if ($projects->count() > 1) {
            $this->throw(
                "Found multiple projects with the same `auth_key` or `name`. Impossible to determine which to use.",
            );
        }

        /** @var ProjectConfiguration $project */
        $project = $projects->first();

        $this->project = $project;
    }
}
