<?php

declare(strict_types=1);

namespace Transl\Actions\Reports;

use Transl\Facades\Transl;

class ReportMissingTranslationKeysAction
{
    /**
     * Execute the action.
     *
     * @param string $key The translation key that is missing (Ex.: `__('nonexistent')`).
     * @param array $replacements The translation placeholder replacements provided.
     * @param string $locale The locale for which the translation key was provided.
     * @param bool $fallback Whether failing to retrieve the translation value in
     * the current locale allowed for testing in the configured fallback locale or not.
     */
    public function execute(string $key, array $replacements, string $locale, bool $fallback): string
    {
        Transl::reports()->missingTranslationKeys()->add($key, $replacements, $locale, $fallback);

        return $key;
    }
}
