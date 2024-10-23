<?php

declare(strict_types=1);

namespace Transl\Support;

use Illuminate\Support\Facades\Process;

class Git
{
    public static function defaultConfiguredBranchName(): ?string
    {
        return static::run(static::getDefaultConfiguredBranchNameCommand());
    }

    public static function currentBranchName(): ?string
    {
        return static::run(static::getCurrentBranchNameCommand());
    }

    public static function getDefaultConfiguredBranchNameCommand(): string
    {
        /**
         * Since v2.28 (around Jul 27, 2020 -> https://github.com/git/git/releases/tag/v2.28.0).
         * @see https://git-scm.com/docs/git-config#Documentation/git-config.txt-initdefaultBranch
         * @see https://github.com/git/git/commit/8747ebb7cde9e90d20794c06e6806f75cd540142
         */
        return 'git config --get init.defaultBranch';
    }

    public static function getCurrentBranchNameCommand(): string
    {
        /**
         * Since v2.22 (around Jun 7, 2019 -> https://github.com/git/git/releases/tag/v2.22.0).
         * @see https://git-scm.com/docs/git-branch#Documentation/git-branch.txt---show-current
         */
        return 'git branch --show-current';
    }

    protected static function run(string $command): ?string
    {
        $result = Process::run($command);

        if ($result->failed()) {
            return null;
        }

        $output = trim($result->output());

        if (blank($output)) {
            return null;
        }

        return $output;
    }
}
