<?php

declare(strict_types=1);

namespace Transl\Exceptions\Branching;

use Transl\Support\Branch;
use Transl\Support\TranslationSet;
use Transl\Config\ProjectConfiguration;
use Transl\Exceptions\Branching\ConflictException;

class CouldNotResolveConflictWhilePulling extends ConflictException
{
    public static function make(
        ProjectConfiguration $project,
        Branch $branch,
        TranslationSet $incoming,
    ): self {
        return static::message(
            "Could not resolve conflicts in `{$incoming->translationKey()}` while pulling the branch `{$branch->name}` of the project `{$project->label()}`.",
        );
    }
}
