<?php

declare(strict_types=1);

namespace Transl\Facades;

use Transl\Transl as TranslMe;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Transl\Support\Versions versions()
 * @method static \Transl\Config\Configuration config(?array $config = null)
 * @method static \Transl\Support\Commands commands()
 * @method static \Transl\Support\Reports\Reports reports()
 * @method static \Transl\Support\Api api()
 * @method static array toArray()
 *
 * @see TranslMe
 */
class Transl extends Facade
{
    public const PACKAGE_NAME = TranslMe::PACKAGE_NAME;
    public const FALLBACK_BRANCH_NAME = TranslMe::FALLBACK_BRANCH_NAME;

    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return TranslMe::class;
    }
}
