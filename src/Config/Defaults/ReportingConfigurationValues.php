<?php

declare(strict_types=1);

namespace Transl\Config\Defaults;

use Illuminate\Contracts\Support\Arrayable;
use Transl\Actions\Reports\ReportMissingTranslationKeysAction;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class ReportingConfigurationValues implements Arrayable
{
    public readonly bool $should_report_missing_translation_keys;
    public readonly string $report_missing_translation_keys_using;
    public readonly bool $silently_discard_exceptions;

    public function __construct()
    {
        $this->should_report_missing_translation_keys = app()->isProduction();
        $this->report_missing_translation_keys_using = ReportMissingTranslationKeysAction::class;
        $this->silently_discard_exceptions = app()->isProduction();
    }

    public static function new(): static
    {
        return new static();
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
}
