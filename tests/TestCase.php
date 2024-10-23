<?php

declare(strict_types=1);

namespace Transl\Tests;

use Transl\TranslServiceProvider;
use Illuminate\Support\Facades\Http;
use Transl\Drivers\LocalFilesDriver;
use Orchestra\Testbench\TestCase as Orchestra;
use Transl\Tests\TestSupport\app\Helpers\Helpers;
use Transl\Config\Enums\BranchingConflictResolutionEnum;
use Transl\Actions\Reports\ReportMissingTranslationKeysAction;
use Transl\Tests\TestSupport\app\Providers\AppServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    public function getEnvironmentSetUp($app): void
    {
        $app->setBasePath($this->getTestSupportDirectory());

        config()->set('transl', [
            'reporting' => [
                'should_report_missing_translation_keys' => true,
                'report_missing_translation_keys_using' => ReportMissingTranslationKeysAction::class,
                'silently_discard_exceptions' => false,
            ],
            'defaults' => [
                'project' => 'example_auth_key',
                'project_options' => [
                    'transl_directory' => storage_path('app/.transl'),
                    'locale' => [
                        'default' => config('app.locale'),
                        'fallback' => config('app.fallback'),
                        'throw_on_disallowed_locale' => true,
                    ],
                    'branching' => [
                        'default_branch_name' => 'default',
                        'mirror_current_branch' => true,
                        'conflict_resolution' => BranchingConflictResolutionEnum::MERGE_BUT_THROW->value,
                    ],
                ],
            ],
            'projects' => [
                [
                    'auth_key' => 'example_auth_key',
                    'name' => 'example_name',
                    'options' => [],
                    'drivers' => [
                        LocalFilesDriver::class,
                    ],
                ],
            ],
        ]);

        Http::preventStrayRequests();

        Http::fake([
            'https://api.transl.me/v0/reports/missing-translation-keys' => Http::response(),
        ]);
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            TranslServiceProvider::class,
            AppServiceProvider::class,
        ];
    }

    protected function helpers(): Helpers
    {
        return Helpers::new();
    }

    protected function getLangDirectory(string $path = ''): string
    {
        return $this->getTestSupportDirectory("/lang/{$path}");
    }

    protected function getFixtureDirectory(string $path = ''): string
    {
        return $this->getTestSupportDirectory("/__fixture/{$path}");
    }

    protected function getTestSupportDirectory(string $path = ''): string
    {
        return $this->getTestDirectory("/TestSupport/{$path}");
    }

    protected function getTestDirectory(string $path = ''): string
    {
        return $this->getPackageDirectory("/tests/{$path}");
    }

    protected function getPackageDirectory(string $path = ''): string
    {
        return rtrim(str_replace(['\\', '//'], '/', dirname(__DIR__) . '/' . $path), '/');
    }
}
