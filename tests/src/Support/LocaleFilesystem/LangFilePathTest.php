<?php

declare(strict_types=1);

use Transl\Support\LocaleFilesystem\FilePath;
use Transl\Support\LocaleFilesystem\LangFilePath;

$new = static function (
    string $root,
    string $relativePath = '',
    string $directorySeparator = DIRECTORY_SEPARATOR,
): LangFilePath {
    return LangFilePath::new($root, $relativePath, $directorySeparator);
};

describe('Base', function () use ($new): void {
    it('extends "FilePath"', function () use ($new): void {
        expect(is_subclass_of($new(__DIR__), FilePath::class))->toEqual(true);
    });

    it('can retrieve the relative vendor path from an unnamed vendor', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/vendor/yolo/en/auth.php', '/')->relativeFromPackage($languageDirectory))->toEqual('en/auth.php');
        expect($new($languageDirectory, '/vendor/yolo/en/auth.php', '/')->relativeFromPackage($new($languageDirectory)))->toEqual('en/auth.php');
    });

    it('can retrieve the relative vendor path from a given vendor name', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/vendor/yolo/en/auth.php', '/')->relativeFromPackage($languageDirectory, 'yolo'))->toEqual('en/auth.php');
        expect($new($languageDirectory, '/vendor/yolo/en/auth.php', '/')->relativeFromPackage($new($languageDirectory), 'yolo'))->toEqual('en/auth.php');
    });

    it('returns the full path when trying to retrieve the relative vendor path from an invalid vendor name', function () use ($new): void {
        $languageDirectory = '/project/lang';
        $langFilePath = $new($languageDirectory, '/vendor/yolo/en/auth.php', '/');

        expect($langFilePath->relativeFromPackage($languageDirectory, 'nope'))->toEqual($langFilePath->fullPath());
        expect($langFilePath->relativeFromPackage($new($languageDirectory), 'nope'))->toEqual($langFilePath->fullPath());
    });

    it("can determine if it's a JSON file or not", function () use ($new): void {
        expect($new('auth.php')->isJson())->toEqual(false);
        expect($new('auth.json')->isJson())->toEqual(true);
        expect($new('auth.nope')->isJson())->toEqual(false);
    });

    it("can determine if it's a PHP file or not", function () use ($new): void {
        expect($new('auth.php')->isPhp())->toEqual(true);
        expect($new('auth.json')->isPhp())->toEqual(false);
        expect($new('auth.nope')->isPhp())->toEqual(false);
    });

    it("can determine if it's a package file or not", function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/vendor/yolo')->isPackage())->toEqual(true);
        expect($new($languageDirectory, '/vendor/yolo/auth.php')->isPackage())->toEqual(true);

        expect($new("{$languageDirectory}/sub_path/", '/vendor/yolo')->isPackage())->toEqual(true);
        expect($new("{$languageDirectory}/sub_path/", '/vendor/yolo/auth.php')->isPackage())->toEqual(true);
        expect($new("{$languageDirectory}/vendor/", '/vendor/yolo')->isPackage())->toEqual(true);
        expect($new("{$languageDirectory}/vendor/", '/vendor/yolo/auth.php')->isPackage())->toEqual(true);

        expect($new("{$languageDirectory}/vendor/", '/')->isPackage())->toEqual(false);
        expect($new("{$languageDirectory}/vendor/", '/yolo')->isPackage())->toEqual(false);
        expect($new("{$languageDirectory}/vendor/", '/yolo/auth.php')->isPackage())->toEqual(false);

        expect($new($languageDirectory, '/vendor/')->isPackage())->toEqual(false);
        expect($new("{$languageDirectory}/sub_path/", '/vendor/')->isPackage())->toEqual(false);
        expect($new("{$languageDirectory}/vendor/", '/vendor/')->isPackage())->toEqual(false);
    });

    it("can determine if it's in a composer vendor directory or not", function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/vendor/yolo')->inVendor())->toEqual(false);
        expect($new($languageDirectory, '/vendor/yolo/auth.php')->inVendor())->toEqual(false);

        expect($new("{$languageDirectory}/sub_path/", '/vendor/yolo')->inVendor())->toEqual(false);
        expect($new("{$languageDirectory}/sub_path/", '/vendor/yolo/auth.php')->inVendor())->toEqual(false);
        expect($new("{$languageDirectory}/vendor/", '/vendor/yolo')->inVendor())->toEqual(true);
        expect($new("{$languageDirectory}/vendor/", '/vendor/yolo/auth.php')->inVendor())->toEqual(true);

        expect($new("{$languageDirectory}/vendor/", '/')->inVendor())->toEqual(true);
        expect($new("{$languageDirectory}/vendor/", '/yolo')->inVendor())->toEqual(true);
        expect($new("{$languageDirectory}/vendor/", '/yolo/auth.php')->inVendor())->toEqual(true);

        expect($new($languageDirectory, '/vendor/')->inVendor())->toEqual(false);
        expect($new("{$languageDirectory}/sub_path/", '/vendor/')->inVendor())->toEqual(false);
        expect($new("{$languageDirectory}/vendor/", '/vendor/')->inVendor())->toEqual(true);
    });
});

describe('PHP lang files', function () use ($new): void {
    /* Guess locale
    ------------------------------------------------*/

    it('can guess the locale from a PHP translation file given a regular file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/en/auth.php')->guessLocale($languageDirectory))->toEqual('en');
        expect($new($languageDirectory, '/unknown/auth.php')->guessLocale($languageDirectory))->toEqual('unknown');

        expect($new($languageDirectory, '/en/auth.php')->guessLocale($new($languageDirectory)))->toEqual('en');
        expect($new($languageDirectory, '/unknown/auth.php')->guessLocale($new($languageDirectory)))->toEqual('unknown');
    });

    it('can guess the locale from a PHP translation file given a sub file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/en/pages/dashboard/nav.php')->guessLocale($languageDirectory))->toEqual('en');
        expect($new($languageDirectory, '/unknown/pages/dashboard/nav.php')->guessLocale($languageDirectory))->toEqual('unknown');

        expect($new($languageDirectory, '/en/pages/dashboard/nav.php')->guessLocale($new($languageDirectory)))->toEqual('en');
        expect($new($languageDirectory, '/unknown/pages/dashboard/nav.php')->guessLocale($new($languageDirectory)))->toEqual('unknown');
    });

    it('can guess the locale from a PHP translation file given a vendor with a regular file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/vendor/yolo/en/auth.php')->guessLocale($languageDirectory))->toEqual('en');
        expect($new($languageDirectory, '/vendor/yolo/unknown/auth.php')->guessLocale($languageDirectory))->toEqual('unknown');

        expect($new($languageDirectory, '/vendor/yolo/en/auth.php')->guessLocale($new($languageDirectory)))->toEqual('en');
        expect($new($languageDirectory, '/vendor/yolo/unknown/auth.php')->guessLocale($new($languageDirectory)))->toEqual('unknown');
    });

    it('can guess the locale from a PHP translation file given a vendor with a sub file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/vendor/yolo/en/pages/dashboard/nav.php')->guessLocale($languageDirectory))->toEqual('en');
        expect($new($languageDirectory, '/vendor/yolo/unknown/pages/dashboard/nav.php')->guessLocale($languageDirectory))->toEqual('unknown');

        expect($new($languageDirectory, '/vendor/yolo/en/pages/dashboard/nav.php')->guessLocale($new($languageDirectory)))->toEqual('en');
        expect($new($languageDirectory, '/vendor/yolo/unknown/pages/dashboard/nav.php')->guessLocale($new($languageDirectory)))->toEqual('unknown');
    });

    /* Guess group
    ------------------------------------------------*/

    it('can guess the group from a PHP translation file given a regular file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/en/auth.php')->guessGroup($languageDirectory))->toEqual('auth');
        expect($new($languageDirectory, '/unknown/auth.php')->guessGroup($languageDirectory))->toEqual('auth');

        expect($new($languageDirectory, '/en/auth.php')->guessGroup($new($languageDirectory)))->toEqual('auth');
        expect($new($languageDirectory, '/unknown/auth.php')->guessGroup($new($languageDirectory)))->toEqual('auth');
    });

    it('can guess the group from a PHP translation file given a sub file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/en/pages/dashboard/nav.php')->guessGroup($languageDirectory))->toEqual('pages/dashboard/nav');
        expect($new($languageDirectory, '/unknown/pages/dashboard/nav.php')->guessGroup($languageDirectory))->toEqual('pages/dashboard/nav');

        expect($new($languageDirectory, '/en/pages/dashboard/nav.php')->guessGroup($new($languageDirectory)))->toEqual('pages/dashboard/nav');
        expect($new($languageDirectory, '/unknown/pages/dashboard/nav.php')->guessGroup($new($languageDirectory)))->toEqual('pages/dashboard/nav');
    });

    it('can guess the group from a PHP translation file given a vendor with a regular file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/vendor/yolo/en/auth.php')->guessGroup($languageDirectory))->toEqual('auth');
        expect($new($languageDirectory, '/vendor/yolo/unknown/auth.php')->guessGroup($languageDirectory))->toEqual('auth');

        expect($new($languageDirectory, '/vendor/yolo/en/auth.php')->guessGroup($new($languageDirectory)))->toEqual('auth');
        expect($new($languageDirectory, '/vendor/yolo/unknown/auth.php')->guessGroup($new($languageDirectory)))->toEqual('auth');
    });

    it('can guess the group from a PHP translation file given a vendor with a sub file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/vendor/yolo/en/pages/dashboard/nav.php')->guessGroup($languageDirectory))->toEqual('pages/dashboard/nav');
        expect($new($languageDirectory, '/vendor/yolo/unknown/pages/dashboard/nav.php')->guessGroup($languageDirectory))->toEqual('pages/dashboard/nav');

        expect($new($languageDirectory, '/vendor/yolo/en/pages/dashboard/nav.php')->guessGroup($new($languageDirectory)))->toEqual('pages/dashboard/nav');
        expect($new($languageDirectory, '/vendor/yolo/unknown/pages/dashboard/nav.php')->guessGroup($new($languageDirectory)))->toEqual('pages/dashboard/nav');
    });

    /* Guess namespace
    ------------------------------------------------*/

    it('can not guess the namespace from a PHP translation file given a regular file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/en/auth.php')->guessNamespace($languageDirectory))->toEqual(null);
        expect($new($languageDirectory, '/unknown/auth.php')->guessNamespace($languageDirectory))->toEqual(null);

        expect($new($languageDirectory, '/en/auth.php')->guessNamespace($new($languageDirectory)))->toEqual(null);
        expect($new($languageDirectory, '/unknown/auth.php')->guessNamespace($new($languageDirectory)))->toEqual(null);
    });

    it('can not guess the namespace from a PHP translation file given a sub file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/en/pages/dashboard/nav.php')->guessNamespace($languageDirectory))->toEqual(null);
        expect($new($languageDirectory, '/unknown/pages/dashboard/nav.php')->guessNamespace($languageDirectory))->toEqual(null);

        expect($new($languageDirectory, '/en/pages/dashboard/nav.php')->guessNamespace($new($languageDirectory)))->toEqual(null);
        expect($new($languageDirectory, '/unknown/pages/dashboard/nav.php')->guessNamespace($new($languageDirectory)))->toEqual(null);
    });

    it('can guess the namespace from a PHP translation file given a vendor with a regular file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/vendor/yolo/en/auth.php')->guessNamespace($languageDirectory))->toEqual('yolo');
        expect($new($languageDirectory, '/vendor/yolo/unknown/auth.php')->guessNamespace($languageDirectory))->toEqual('yolo');

        expect($new($languageDirectory, '/vendor/yolo/en/auth.php')->guessNamespace($new($languageDirectory)))->toEqual('yolo');
        expect($new($languageDirectory, '/vendor/yolo/unknown/auth.php')->guessNamespace($new($languageDirectory)))->toEqual('yolo');
    });

    it('can guess the namespace from a PHP translation file given a vendor with a sub file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/vendor/yolo/en/pages/dashboard/nav.php')->guessNamespace($languageDirectory))->toEqual('yolo');
        expect($new($languageDirectory, '/vendor/yolo/unknown/pages/dashboard/nav.php')->guessNamespace($languageDirectory))->toEqual('yolo');

        expect($new($languageDirectory, '/vendor/yolo/en/pages/dashboard/nav.php')->guessNamespace($new($languageDirectory)))->toEqual('yolo');
        expect($new($languageDirectory, '/vendor/yolo/unknown/pages/dashboard/nav.php')->guessNamespace($new($languageDirectory)))->toEqual('yolo');
    });
});

describe('JSON lang files', function () use ($new): void {
    /* Guess locale
    ------------------------------------------------*/

    it('can guess the locale from a JSON translation file given a regular file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/en.json')->guessLocale($languageDirectory))->toEqual('en');
        expect($new($languageDirectory, '/unknown.json')->guessLocale($languageDirectory))->toEqual('unknown');

        expect($new($languageDirectory, '/en.json')->guessLocale($new($languageDirectory)))->toEqual('en');
        expect($new($languageDirectory, '/unknown.json')->guessLocale($new($languageDirectory)))->toEqual('unknown');
    });

    it('can guess the locale from a JSON translation file given a sub file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/pages/dashboard/nav/en.json')->guessLocale($languageDirectory))->toEqual('en');
        expect($new($languageDirectory, '/pages/dashboard/nav/unknown.json')->guessLocale($languageDirectory))->toEqual('unknown');

        expect($new($languageDirectory, '/pages/dashboard/nav/en.json')->guessLocale($new($languageDirectory)))->toEqual('en');
        expect($new($languageDirectory, '/pages/dashboard/nav/unknown.json')->guessLocale($new($languageDirectory)))->toEqual('unknown');
    });

    it('can guess the locale from a JSON translation file given a vendor with a regular file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/vendor/yolo/en.json')->guessLocale($languageDirectory))->toEqual('en');
        expect($new($languageDirectory, '/vendor/yolo/unknown.json')->guessLocale($languageDirectory))->toEqual('unknown');

        expect($new($languageDirectory, '/vendor/yolo/en.json')->guessLocale($new($languageDirectory)))->toEqual('en');
        expect($new($languageDirectory, '/vendor/yolo/unknown.json')->guessLocale($new($languageDirectory)))->toEqual('unknown');
    });

    it('can guess the locale from a JSON translation file given a vendor with a sub file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/vendor/yolo/pages/dashboard/nav/en.json')->guessLocale($languageDirectory))->toEqual('en');
        expect($new($languageDirectory, '/vendor/yolo/pages/dashboard/nav/unknown.json')->guessLocale($languageDirectory))->toEqual('unknown');

        expect($new($languageDirectory, '/vendor/yolo/pages/dashboard/nav/en.json')->guessLocale($new($languageDirectory)))->toEqual('en');
        expect($new($languageDirectory, '/vendor/yolo/pages/dashboard/nav/unknown.json')->guessLocale($new($languageDirectory)))->toEqual('unknown');
    });

    /* Guess group
    ------------------------------------------------*/

    it('can not guess the group from a JSON translation file given a regular file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/en.json')->guessGroup($languageDirectory))->toEqual(null);
        expect($new($languageDirectory, '/unknown.json')->guessGroup($languageDirectory))->toEqual(null);

        expect($new($languageDirectory, '/en.json')->guessGroup($new($languageDirectory)))->toEqual(null);
        expect($new($languageDirectory, '/unknown.json')->guessGroup($new($languageDirectory)))->toEqual(null);
    });

    it('can not guess the group from a JSON translation file given a sub file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/pages/dashboard/nav/en.json')->guessGroup($languageDirectory))->toEqual(null);
        expect($new($languageDirectory, '/pages/dashboard/nav/unknown.json')->guessGroup($languageDirectory))->toEqual(null);

        expect($new($languageDirectory, '/pages/dashboard/nav/en.json')->guessGroup($new($languageDirectory)))->toEqual(null);
        expect($new($languageDirectory, '/pages/dashboard/nav/unknown.json')->guessGroup($new($languageDirectory)))->toEqual(null);
    });

    it('can not guess the group from a JSON translation file given a vendor with a regular file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/vendor/yolo/auth/en.json')->guessGroup($languageDirectory))->toEqual(null);
        expect($new($languageDirectory, '/vendor/yolo/auth/unknown.json')->guessGroup($languageDirectory))->toEqual(null);

        expect($new($languageDirectory, '/vendor/yolo/auth/en.json')->guessGroup($new($languageDirectory)))->toEqual(null);
        expect($new($languageDirectory, '/vendor/yolo/auth/unknown.json')->guessGroup($new($languageDirectory)))->toEqual(null);
    });

    it('can not guess the group from a JSON translation file given a vendor with a sub file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/vendor/yolo/pages/dashboard/nav/en.json')->guessGroup($languageDirectory))->toEqual(null);
        expect($new($languageDirectory, '/vendor/yolo/pages/dashboard/nav/unknown.json')->guessGroup($languageDirectory))->toEqual(null);

        expect($new($languageDirectory, '/vendor/yolo/pages/dashboard/nav/en.json')->guessGroup($new($languageDirectory)))->toEqual(null);
        expect($new($languageDirectory, '/vendor/yolo/pages/dashboard/nav/unknown.json')->guessGroup($new($languageDirectory)))->toEqual(null);
    });

    /* Guess namespace
    ------------------------------------------------*/

    it('can not guess the namespace from a JSON translation file given a regular file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/en.json')->guessNamespace($languageDirectory))->toEqual(null);
        expect($new($languageDirectory, '/unknown.json')->guessNamespace($languageDirectory))->toEqual(null);

        expect($new($languageDirectory, '/en.json')->guessNamespace($new($languageDirectory)))->toEqual(null);
        expect($new($languageDirectory, '/unknown.json')->guessNamespace($new($languageDirectory)))->toEqual(null);
    });

    it('can not guess the namespace from a JSON translation file given a sub file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/pages/dashboard/nav/en.json')->guessNamespace($languageDirectory))->toEqual(null);
        expect($new($languageDirectory, '/pages/dashboard/nav/unknown.json')->guessNamespace($languageDirectory))->toEqual(null);

        expect($new($languageDirectory, '/pages/dashboard/nav/en.json')->guessNamespace($new($languageDirectory)))->toEqual(null);
        expect($new($languageDirectory, '/pages/dashboard/nav/unknown.json')->guessNamespace($new($languageDirectory)))->toEqual(null);
    });

    it('can not guess the namespace from a JSON translation file given a vendor with a regular file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/vendor/yolo/auth/en.json')->guessNamespace($languageDirectory))->toEqual(null);
        expect($new($languageDirectory, '/vendor/yolo/auth/unknown.json')->guessNamespace($languageDirectory))->toEqual(null);

        expect($new($languageDirectory, '/vendor/yolo/auth/en.json')->guessNamespace($new($languageDirectory)))->toEqual(null);
        expect($new($languageDirectory, '/vendor/yolo/auth/unknown.json')->guessNamespace($new($languageDirectory)))->toEqual(null);
    });

    it('can not guess the namespace from a JSON translation file given a vendor with a sub file path', function () use ($new): void {
        $languageDirectory = '/project/lang';

        expect($new($languageDirectory, '/vendor/yolo/pages/dashboard/nav/en.json')->guessNamespace($languageDirectory))->toEqual(null);
        expect($new($languageDirectory, '/vendor/yolo/pages/dashboard/nav/unknown.json')->guessNamespace($languageDirectory))->toEqual(null);

        expect($new($languageDirectory, '/vendor/yolo/pages/dashboard/nav/en.json')->guessNamespace($new($languageDirectory)))->toEqual(null);
        expect($new($languageDirectory, '/vendor/yolo/pages/dashboard/nav/unknown.json')->guessNamespace($new($languageDirectory)))->toEqual(null);
    });
});
