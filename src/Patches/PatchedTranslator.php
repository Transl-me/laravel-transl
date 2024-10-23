<?php

declare(strict_types=1);

namespace Transl\Patches;

use Illuminate\Translation\Translator;

/**
 * This class exist to bring Laravel 10 versions
 * older than v10.43.0 on par with v10.43.0.
 *
 * This includes the "Handle missing translation keys"
 * feature added in v10.33.0 (+patched in v10.37.0):
 * - https://github.com/laravel/framework/pull/49040
 * - https://github.com/laravel/framework/pull/49341
 *
 * This includes the "Incorrect locale reported"
 * fix made by us in v10.43.0:
 * - https://github.com/laravel/framework/pull/49900
 *
 * Based on: https://github.com/laravel/framework/blob/v10.43.0/src/Illuminate/Translation/Translator.php
 */
class PatchedTranslator extends Translator
{
    /**
     * The callback that is responsible for handling missing translation keys.
     *
     * @var callable|null
     */
    protected $missingTranslationKeyCallback;

    /**
     * Indicates whether missing translation keys should be handled.
     *
     * @var bool
     */
    protected $handleMissingTranslationKeys = true;

    /**
     * Get the translation for the given key.
     *
     * @param  string  $key
     * @param  array  $replace
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string|array
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $locale = $locale ?: $this->locale;

        // For JSON translations, there is only one file per locale, so we will simply load
        // that file and then we will be ready to check the array for the key. These are
        // only one level deep so we do not need to do any fancy searching through it.
        $this->load('*', '*', $locale);

        $line = $this->loaded['*']['*'][$locale][$key] ?? null;

        // If we can't find a translation for the JSON key, we will attempt to translate it
        // using the typical translation file. This way developers can always just use a
        // helper such as __ instead of having to pick between trans or __ with views.
        if (! isset($line)) {
            [$namespace, $group, $item] = $this->parseKey($key);

            // Here we will get the locale that should be used for the language line. If one
            // was not passed, we will use the default locales which was given to us when
            // the translator was instantiated. Then, we can load the lines and return.
            $locales = $fallback ? $this->localeArray($locale) : [$locale];

            foreach ($locales as $languageLineLocale) {
                if (! is_null($line = $this->getLine(
                    $namespace, $group, $languageLineLocale, $item, $replace
                ))) {
                    return $line;
                }
            }

            $key = $this->handleMissingTranslationKey(
                $key, $replace, $locale, $fallback
            );
        }

        // If the line doesn't exist, we will return back the key which was requested as
        // that will be quick to spot in the UI if language keys are wrong or missing
        // from the application's language files. Otherwise we can return the line.
        return $this->makeReplacements($line ?: $key, $replace);
    }

    /**
     * Handle a missing translation key.
     *
     * @param  string  $key
     * @param  array  $replace
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string
     */
    protected function handleMissingTranslationKey($key, $replace, $locale, $fallback)
    {
        if (! $this->handleMissingTranslationKeys ||
            ! isset($this->missingTranslationKeyCallback)) {
            return $key;
        }

        // Prevent infinite loops...
        $this->handleMissingTranslationKeys = false;

        $key = call_user_func(
            $this->missingTranslationKeyCallback,
            $key, $replace, $locale, $fallback
        ) ?? $key;

        $this->handleMissingTranslationKeys = true;

        return $key;
    }

    /**
     * Register a callback that is responsible for handling missing translation keys.
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function handleMissingKeysUsing(?callable $callback)
    {
        $this->missingTranslationKeyCallback = $callback;

        return $this;
    }
}
