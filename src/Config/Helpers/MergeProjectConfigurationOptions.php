<?php

declare(strict_types=1);

namespace Transl\Config\Helpers;

use Transl\Config\Values\ProjectConfigurationOptions;

class MergeProjectConfigurationOptions
{
    public static function mergeWithDefaults(
        array|ProjectConfigurationOptions $options,
        ProjectConfigurationOptions $defaults,
    ): ProjectConfigurationOptions {
        if (($options instanceof ProjectConfigurationOptions)) {
            return $options;
        }

        $options = [
            ...$options,
            'transl_directory' => static::determineTranslDirectory($options, $defaults),
            'locale' => [
                'default' => $options['locale']['default'] ?? $defaults->locale->default,
                'fallback' => $options['locale']['fallback'] ?? $defaults->locale->fallback,
                'allowed' => $options['locale']['allowed'] ?? $defaults->locale->allowed,
                'throw_on_disallowed_locale' => $options['locale']['throw_on_disallowed_locale'] ?? $defaults->locale->throw_on_disallowed_locale,
            ],
            'branching' => [
                'default_branch_name' => $options['branching']['default_branch_name'] ?? $defaults->branching->default_branch_name,
                'mirror_current_branch' => $options['branching']['mirror_current_branch'] ?? $defaults->branching->mirror_current_branch,
                'conflict_resolution' => $options['branching']['conflict_resolution'] ?? $defaults->branching->conflict_resolution,
            ],
        ];

        return ProjectConfigurationOptions::new(...$options);
    }

    protected static function determineTranslDirectory(array $options, ProjectConfigurationOptions $defaults): ?string
    {
        $value = $options['transl_directory'] ?? null;

        if ($value === false) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return $defaults->transl_directory;
    }
}
