<?php

declare(strict_types=1);

use Transl\Drivers\LocalFilesDriver;
use Transl\Config\Enums\BranchingConflictResolutionEnum;
use Transl\Actions\Reports\ReportMissingTranslationKeysAction;

return [
    'reporting' => [
        /**
         * Whether translation keys used but for which
         * no corresponding translation value could be
         * found should be reported to Transl.
         *
         * Ex.: `__('nonexistent')` -> reports "nonexistent".
         *
         * By default, enabled only on `app()->isProduction()`.
         */
        'should_report_missing_translation_keys' => null,

        /**
         * The class that should be used to report missing translation
         * keys. The class should have either an `__invokable` or
         * `execute` method.
         */
        'report_missing_translation_keys_using' => ReportMissingTranslationKeysAction::class,

        /**
         * Whether exceptions thrown during the catching and reporting
         * process should be silently discarded. Probably best to
         * enable this in production environnements.
         *
         * By default, enabled only on `app()->isProduction()`.
         */
        'silently_discard_exceptions' => null,
    ],

    'defaults' => [
        /**
         * Default project options that will be used in filling
         * a given project's option that hasn't been given a value.
         * In other words, fallback option values for a given project.
         *
         * The exact same as `project.options`.
         *
         * @see `\Transl\Config\Values\ProjectConfigurationOptions`
         */
        'project_options' => [
            /**
             * A local directory used to store/cache/track
             * necessary informations.
             *
             * - If set to `null`, `storage_path('app/.transl')` will be used.
             * - If set to `false`, the feature will be disabled (conflicts won't be detected).
             */
            'transl_directory' => storage_path('app/.transl'),

            /**
             * The project's branching specific configurations.
             */
            'branching' => [
                /**
                 * The default branch name to use in contexts where
                 * none was provided and/or none could be determined
                 * either because of limitations or configurations.
                 */
                'default_branch_name' => 'main',

                /**
                 * Whether local Git branches, when pushing translation
                 * lines to Transl, should be reflected on Transl.
                 */
                'mirror_current_branch' => true,

                /**
                 * How detected conflicts should be handled.
                 * Check the Enum for more details and values.
                 */
                'conflict_resolution' => BranchingConflictResolutionEnum::MERGE_BUT_THROW->value,
            ],
        ],
    ],

    'projects' => [
        [
            /**
             * The project's authentication key.
             * Used to both identify the project and
             * the user making local and remote changes.
             *
             * This value should be unique per team members/bots.
             * This value should be created/retrieved/refreshed from Transl.
             */
            'auth_key' => env('TRANSL_KEY', 'Empty `TRANSL_KEY` environnement variable.'),

            /**
             * A user friendly name given to the project.
             * Used when printing the project back to the user
             * in console outputs, exception messages etc... .
             *
             * Falls back to be a truncated and redacted version
             * of the authentication key.
             */
            'name' => 'My first project',

            /**
             * The project's configuration options.
             * Used to configure behaviors.
             *
             * Same as and merged with (overwriting) "project_options"
             * in the above "defaults" key.
             *
             * @see `\Transl\Config\Values\ProjectConfigurationOptions`
             */
            'options' => [],

            /**
             * The project's configuration drivers.
             * Used for identifying, retrieving, updating and handling
             * translation contents.
             */
            'drivers' => [
                /**
                 * A driver scanning local directories for translation files
                 * based on Laravel's default behavior.
                 *
                 * Check out the classe's constructor properties for a full
                 * list of possible params and their description.
                 */
                LocalFilesDriver::class => [
                    'language_directories' => array_filter([
                        /**
                         * Ensure the language directory is first published
                         * with: `php artisan lang:publish`.
                         */
                        file_exists(lang_path()) ? lang_path() : '',
                    ]),
                ],
            ],
        ],
    ],
];
