<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Transl\Drivers\LocalFilesDriver;
use Illuminate\Filesystem\Filesystem;
use Transl\Exceptions\LocalFilesDriver\MissingRequiredTranslationSetGroup;
use Transl\Actions\LocalFilesDriver\GetTranslationContentsFromLocalFilesAction;

describe('it works', function (): void {
    // $files = app(Filesystem::class)->allFiles($this->getLangDirectory());
    $files = app(Filesystem::class)->allFiles(dirname(__DIR__, 3) . '/TestSupport/lang');

    foreach ($files as $file) {
        $relativePath = str($file->getRelativePathname())->replace(DIRECTORY_SEPARATOR, '/');

        $locale = $relativePath->before('/')->before('.')->value();
        $group = $relativePath->after('/')->before('.')->value();
        $namespace = null;

        if ($relativePath->contains('vendor/')) {
            $namespace = $relativePath->after('vendor/')->before('/')->value();
            $locale = $relativePath->after("vendor/{$namespace}/")->before('/')->before('.')->value();
            $group = $relativePath->after("vendor/{$namespace}/")->after('/')->before('.')->value();
        }

        if ($group === $locale) {
            $group = null;
        }

        $testName = $namespace ? "[{$locale}] - {$namespace}::" : "[{$locale}] - ";
        $testName = $group ? "{$testName}{$group}" : "{$testName}[JSON file]";

        test($testName, function () use ($locale, $group, $namespace): void {
            $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
            $result = $isntance->execute($locale, $group, $namespace);

            expect($result)->toMatchSnapshot();
        });
    }
});

it('loads the JSON files when no group or namespace are given', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('en', null, null);

    expect($result)->toEqual(app(Filesystem::class)->json($this->getLangDirectory('en.json')));
});

it('throws an exception when no group is given but a namespace is given', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());

    expect(static fn () => $isntance->execute('en', null, 'yolo'))->toThrow(MissingRequiredTranslationSetGroup::class);
});

test('en/pages/dashboard/nav', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('en', 'pages/dashboard/nav', null);

    expect($result)->toEqual(__('pages/dashboard/nav', [], 'en'));
});

test('en/auth', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('en', 'auth', null);

    expect($result)->toEqual(__('auth', [], 'en'));
});

test('en/email', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('en', 'email', null);

    expect($result)->toEqual(__('email', [], 'en'));
});

test('en/flash', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('en', 'flash', null);

    expect($result)->toEqual(__('flash', [], 'en'));
});

test('en/value_types', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('en', 'value_types', null);

    expect($result)->toEqual(__('value_types', [], 'en'));
});

test('vendor/some_package/en/pages/dashboard/nav', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('en', 'pages/dashboard/nav', 'some_package');

    expect($result)->toEqual(__('some_package::pages/dashboard/nav', [], 'en'));
});

test('vendor/some_package/en/auth', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('en', 'auth', 'some_package');

    expect($result)->toEqual(__('some_package::auth', [], 'en'));
});

test('vendor/some_package/en/example', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('en', 'example', 'some_package');

    expect($result)->toEqual(__('some_package::example', [], 'en'));
});

test('en.json', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('en', null, null);

    expect(Arr::get($result, 'Hello'))->toEqual(__('Hello', [], 'en'));
    expect(Arr::get($result, 'pages.dashboard.nav.users.billing'))->toEqual(__('pages.dashboard.nav.users.billing', [], 'en'));

    expect(Arr::get($result, 'pages.dashboard.nav.users.logout'))->toEqual("[JSON] overriden 'pages.dashboard.nav.users.logout'!");
    expect(__('pages.dashboard.nav.users.logout', [], 'en'))->toEqual('pages.dashboard.nav.users.logout');

    expect(Arr::get($result, 'null'))->toEqual(null);
    expect(__('null', [], 'en'))->toEqual('null');

    expect(Arr::get($result, 'string'))->toEqual(__('string', [], 'en'));
    expect(Arr::get($result, 'true'))->toEqual(__('true', [], 'en'));

    expect(Arr::get($result, 'false'))->toEqual(false);
    expect(__('false', [], 'en'))->toEqual('false');

    expect(Arr::get($result, 'int'))->toEqual(__('int', [], 'en'));
    expect(Arr::get($result, 'float'))->toEqual(__('float', [], 'en'));
    expect(Arr::get($result, 'string_null'))->toEqual(__('string_null', [], 'en'));
    expect(Arr::get($result, 'string_true'))->toEqual(__('string_true', [], 'en'));
    expect(Arr::get($result, 'string_false'))->toEqual(__('string_false', [], 'en'));
    expect(Arr::get($result, 'string_int'))->toEqual(__('string_int', [], 'en'));
    expect(Arr::get($result, 'string_float'))->toEqual(__('string_float', [], 'en'));

    expect(Arr::get($result, 'string_empty'))->toEqual('');
    expect(__('string_empty', [], 'en'))->toEqual('string_empty');

    expect(Arr::get($result, 'array_empty'))->toEqual([]);
    expect(__('array_empty', [], 'en'))->toEqual('array_empty');
});

test('fr/pages/dashboard/nav', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('fr', 'pages/dashboard/nav', null);

    expect($result)->toEqual((new LocalFilesDriver())->translationLoader()->load('fr', 'pages/dashboard/nav', null));
    expect($result)->toEqual([
        'users' => [
            'profile' => '[FR] Profile',
            'billing' => '[FR] Billing',
            'password' => '[FR] Password',
            'logout' => '[FR] Log out',
        ],
    ]);
    expect(__('pages/dashboard/nav', [], 'fr'))->toEqual([
        'users' => [
            'logout' => "[FR][JSON][bis] overriden 'pages.dashboard.nav.users.logout'!",
        ],
    ]);
});

test('fr/auth', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('fr', 'auth', null);

    expect($result)->toEqual(__('auth', [], 'fr'));
});

test('vendor/some_package/fr/pages/dashboard/nav', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('fr', 'pages/dashboard/nav', 'some_package');

    expect($result)->toEqual(__('some_package::pages/dashboard/nav', [], 'fr'));
});

test('fr.json', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('fr', null, null);

    expect(Arr::get($result, 'Hello'))->toEqual(__('Hello', [], 'fr'));
    expect(Arr::get($result, 'pages.dashboard.nav.users.billing'))->toEqual(__('pages.dashboard.nav.users.billing', [], 'fr'));

    expect(Arr::get($result, 'pages.dashboard.nav.users.logout'))->toEqual("[FR][JSON] overriden 'pages.dashboard.nav.users.logout'!");
    expect(__('pages.dashboard.nav.users.logout', [], 'fr'))->toEqual('pages.dashboard.nav.users.logout');
});

test('vendor/laravel/framework/src/Illuminate/Translation/lang/en/pagination', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('en', 'pagination', null);

    expect($result)->toEqual(__('pagination', [], 'en'));
});

test('vendor/laravel/framework/src/Illuminate/Translation/lang/en/passwords', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('en', 'passwords', null);

    expect($result)->toEqual(__('passwords', [], 'en'));
});

test('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation', function (): void {
    $isntance = (new GetTranslationContentsFromLocalFilesAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute('en', 'validation', null);

    expect($result)->toEqual(__('validation', [], 'en'));
});
