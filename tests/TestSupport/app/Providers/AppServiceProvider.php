<?php

declare(strict_types=1);

namespace Transl\Tests\TestSupport\app\Providers;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom('', 'some_package');
    }
}
