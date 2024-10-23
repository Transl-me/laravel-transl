<?php

declare(strict_types=1);

namespace Transl;

use Transl\Support\Api;
use Transl\Support\Commands;
use Transl\Support\Versions;
use Transl\Config\Configuration;
use Transl\Support\Reports\Reports;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, array>
 */
class Transl implements Arrayable
{
    public const PACKAGE_NAME = 'transl-me/laravel-transl';
    public const FALLBACK_BRANCH_NAME = 'main';

    public function versions(): Versions
    {
        return Versions::instance();
    }

    public function config(?array $config = null): Configuration
    {
        return Configuration::instance($config ?? config('transl'));
    }

    public function commands(): Commands
    {
        return Commands::instance();
    }

    public function reports(): Reports
    {
        return Reports::instance();
    }

    public function api(): Api
    {
        return Api::instance();
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'versions' => $this->versions()->toArray(),
            'config' => $this->config()->toArray(),
        ];
    }
}
