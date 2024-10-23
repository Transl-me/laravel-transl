<?php

declare(strict_types=1);

namespace Transl;

use Throwable;
use Transl\Facades\Transl;
use Illuminate\Support\Facades\Lang;
use Transl\Patches\PatchedTranslator;
use Transl\Commands\TranslInitCommand;
use Transl\Commands\TranslPullCommand;
use Transl\Commands\TranslPushCommand;
use Spatie\LaravelPackageTools\Package;
use Transl\Commands\TranslSynchCommand;
use Transl\Commands\TranslAnalyseCommand;
use Illuminate\Contracts\Support\DeferrableProvider;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Transl\Support\Reports\MissingTranslationKeys\MissingTranslationKeys;

class TranslServiceProvider extends PackageServiceProvider implements DeferrableProvider
{
    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        /**
         * Defer the service provider.
         * Should be loaded after all `Illuminate\*` providers and thus,
         * after `Illuminate\Translation\TranslationServiceProvider`.
         */
        return [static::class];
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('transl')
            ->hasConfigFile()
            ->hasCommands([
                TranslPushCommand::class,
                TranslPullCommand::class,
                TranslSynchCommand::class,
                TranslInitCommand::class,
                TranslAnalyseCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(MissingTranslationKeys::class);

        if (!$this->shouldPatchTranslator()) {
            return;
        }

        $this->patchTranslator();
    }

    public function packageBooted(): void
    {
        Lang::resolved(function (TranslatorContract $translator): void {
            if (!$this->shouldHandleMissingTranslationKeys()) {
                return;
            }

            if (!$this->canHandleMissingTranslationKeys($translator)) {
                return;
            }

            $this->handleMissingTranslationKeys($translator);
        });
    }

    protected function shouldPatchTranslator(): bool
    {
        return version_compare($this->app->version(), '10.43.0') < 0;
    }

    protected function shouldHandleMissingTranslationKeys(): bool
    {
        return Transl::config()->reporting()->should_report_missing_translation_keys;
    }

    protected function canHandleMissingTranslationKeys(TranslatorContract $translator): bool
    {
        /**
         * - Laravel v10.33.0 (https://github.com/laravel/framework/releases/tag/v10.33.0)
         * - Docs: https://laravel.com/docs/10.x/localization#handling-missing-translation-strings.
         */
        if (!method_exists($translator, 'handleMissingKeysUsing')) {
            return false;
        }

        return (bool) Transl::config()->reporting()->report_missing_translation_keys_using;
    }

    protected function patchTranslator(): void
    {
        /**
         * @see vendor/laravel/framework/src/Illuminate/Translation/TranslationServiceProvider.php
         */
        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app->getLocale();

            $trans = new PatchedTranslator($loader, $locale);

            $trans->setFallback($app->getFallbackLocale());

            return $trans;
        });
    }

    protected function handleMissingTranslationKeys(TranslatorContract $translator): void
    {
        try {
            Lang::handleMissingKeysUsing(function (...$args): mixed {
                $use = Transl::config()->reporting()->report_missing_translation_keys_using;
                $method = method_exists($use, 'execute') ? 'execute' : '__invoke';

                return app($use)->{$method}(...$args);
            });
        } catch (Throwable $th) {
            if (Transl::config()->reporting()->silently_discard_exceptions) {
                return;
            }

            throw $th;
        }
    }
}
