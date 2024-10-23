<?php

declare(strict_types=1);

namespace Transl\Exceptions\Report\MissingTranslationKeys;

use Transl\Exceptions\Report\MissingTranslationKeys\MissingTranslationKeysException;

class CouldNotBuildReport extends MissingTranslationKeysException
{
    public static function fromMissingProject(?string $project): self
    {
        return static::message(
            $project
                ? "Could not build a `MissingTranslationKeyReport` as no project could be determined from the provided value `{$project}`."
                : 'Could not build a `MissingTranslationKeyReport` as no target project could be determined nor was provided',
        );
    }

    public static function fromMissingBranch(?string $branch): self
    {
        return static::message(
            $branch
                ? "Could not build a `MissingTranslationKeyReport` as no branch could be determined from the provided value `{$branch}`."
                : 'Could not build a `MissingTranslationKeyReport` as no target branch could be determined nor was provided',
        );
    }
}
