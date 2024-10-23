<?php

declare(strict_types=1);

namespace Transl\Config;

use Illuminate\Contracts\Support\Arrayable;
use Transl\Config\Defaults\ReportingConfigurationValues;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class ReportingConfiguration implements Arrayable
{
    /**
     * Whether translation keys used but for which
     * no corresponding translation value could be
     * found should be reported to Transl.
     *
     * Ex.: `__('nonexistent')` -> reports "nonexistent".
     */
    public readonly bool $should_report_missing_translation_keys;

    /**
     * The class that should be used to report missing translation
     * keys. The class should have either an `__invokable` or
     * `execute` method.
     */
    public readonly string $report_missing_translation_keys_using;

    /**
     * Whether exceptions thrown during the catching and reporting
     * process should be silently discarded. Probably best to
     * enable this in production environnements.
     */
    public readonly bool $silently_discard_exceptions;

    public function __construct(array $values, ReportingConfigurationValues $defaults)
    {
        $this->hydrateProperties($values, $defaults);
    }

    /**
     * Named constructor.
     */
    public static function new(array $values, ReportingConfigurationValues $defaults): static
    {
        return new static($values, $defaults);
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'should_report_missing_translation_keys' => $this->should_report_missing_translation_keys,
            'report_missing_translation_keys_using' => $this->report_missing_translation_keys_using,
            'silently_discard_exceptions' => $this->silently_discard_exceptions,
        ];
    }

    protected function hydrateProperties(array $values, ReportingConfigurationValues $defaults): void
    {
        // @phpstan-ignore-next-line
        $this->should_report_missing_translation_keys = (
            $values['should_report_missing_translation_keys'] ?? $defaults->should_report_missing_translation_keys
        );

        // @phpstan-ignore-next-line
        $this->report_missing_translation_keys_using = (
            $values['report_missing_translation_keys_using'] ?? $defaults->report_missing_translation_keys_using
        );

        // @phpstan-ignore-next-line
        $this->silently_discard_exceptions = (
            $values['silently_discard_exceptions'] ?? $defaults->silently_discard_exceptions
        );
    }
}
