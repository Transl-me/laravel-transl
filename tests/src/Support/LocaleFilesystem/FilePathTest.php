<?php

declare(strict_types=1);

use Transl\Support\LocaleFilesystem\FilePath;

$_ = static fn (string $path, string $separator): string => str_replace(['\\', '/'], $separator, $path);

it('can be newed up', function (): void {
    expect(FilePath::new(__DIR__) instanceof FilePath)->toEqual(true);
});

it('can wrap up a given value', function (): void {
    expect(FilePath::wrap(__DIR__)->fullPath())->toEqual(__DIR__);
    expect(FilePath::wrap(FilePath::new(__DIR__))->fullPath())->toEqual(__DIR__);
});

it('can retrieve the root', function (): void {
    expect(FilePath::new(__DIR__)->root())->toEqual(__DIR__);
});

it('can retrieve the relative path', function (): void {
    expect(FilePath::new(__DIR__, 'yolo')->relativePath())->toEqual('yolo');
});

it('can retrieve the directory separator', function (): void {
    expect(FilePath::new('', '', '|')->directorySeparator())->toEqual('|');
});

it("doesn't trim the root's leading slash", function (): void {
    expect(FilePath::new('/' . __DIR__)->root())->toEqual((windows_os() ? DIRECTORY_SEPARATOR : '') . __DIR__);
});

it("doesn't trim the root's leading backslash", function (): void {
    expect(FilePath::new('\\' . __DIR__)->root())->toEqual((windows_os() ? DIRECTORY_SEPARATOR : '') . __DIR__);
});

it("trims the root's trailing slash", function (): void {
    expect(FilePath::new(__DIR__ . '/')->root())->toEqual(__DIR__);
});

it("trims the root's trailing backslash", function (): void {
    expect(FilePath::new(__DIR__ . '\\')->root())->toEqual(__DIR__);
});

it("trims the relative path's leading & trailing slashes", function (): void {
    expect(FilePath::new(__DIR__, '/yolo/')->relativePath())->toEqual('yolo');
});

it("trims the relative path's leading & trailing backslashes", function (): void {
    expect(FilePath::new(__DIR__, '\\yolo\\')->relativePath())->toEqual('yolo');
});

it("the root's path directory separator get standardized", function (): void {
    expect(FilePath::new('/root/parent\\child/grand_child\\sub', '', '|')->root())->toEqual('|root|parent|child|grand_child|sub');
});

it("the relative path's path directory separator get standardized", function (): void {
    expect(FilePath::new('', '/root/parent\\child/grand_child\\sub', '|')->relativePath())->toEqual('root|parent|child|grand_child|sub');
});

it("the root's path directory separator get standardized again on separator update", function (): void {
    expect(
        FilePath::new('/root/parent\\child/grand_child\\sub', '')->withDirectorySeparator('|')->root(),
    )->toEqual('|root|parent|child|grand_child|sub');
});

it("the relative path's path directory separator get standardized again on separator update", function (): void {
    expect(
        FilePath::new('', '/root/parent\\child/grand_child\\sub')->withDirectorySeparator('|')->relativePath(),
    )->toEqual('root|parent|child|grand_child|sub');
});

it('can retrieve the full path', function () use ($_): void {
    expect(FilePath::new(__DIR__, 'yolo')->fullPath())->toEqual(__DIR__ . DIRECTORY_SEPARATOR . 'yolo');
    expect(FilePath::new(__DIR__, 'yolo', '/')->fullPath())->toEqual($_(__DIR__ . '/yolo', '/'));
    expect(FilePath::new(__DIR__, 'yolo', '\\')->fullPath())->toEqual($_(__DIR__ . '/yolo', '\\'));
    expect(FilePath::new(__DIR__, 'yolo', '|')->fullPath())->toEqual($_(__DIR__ . '/yolo', '|'));
});

it('can retrieve the directory name', function (): void {
    expect(FilePath::new(__DIR__, 'yolo.php')->directoryName())->toEqual('LocaleFilesystem');
});

it('can retrieve the file name', function (): void {
    expect(FilePath::new(__DIR__, 'yolo.php')->fileName())->toEqual('yolo.php');
});

it('can retrieve the file name without extension', function (): void {
    expect(FilePath::new(__DIR__, 'yolo.php')->fileNameWithoutExtension())->toEqual('yolo');
});

it('can retrieve the file extension', function (): void {
    expect(FilePath::new(__DIR__, 'yolo.php')->extension())->toEqual('php');
});

it('can append a given path', function () use ($_): void {
    expect(
        FilePath::new(__DIR__, 'yolo', '|')->append('\\yo\\lo\\file.php')->fullPath(),
    )->toEqual($_(__DIR__ . DIRECTORY_SEPARATOR . 'yolo/yo/lo/file.php', '|'));
});

it('can retrieve the relative path from a given string root', function (): void {
    expect(FilePath::new(__DIR__, 'yolo')->relativeFrom(__DIR__))->toEqual('yolo');
});

it('can retrieve the relative path from a given "FilePath" root', function (): void {
    expect(FilePath::new(__DIR__, 'yolo')->relativeFrom(FilePath::new(__DIR__)))->toEqual('yolo');
});

it('returns the fullpath when the given root is not a parent directory', function (): void {
    expect(
        FilePath::new(__DIR__, 'yolo')->relativeFrom(__DIR__ . '/nope'),
    )->toEqual(__DIR__ . DIRECTORY_SEPARATOR . 'yolo');
});

it('returns an empty string when the given root is the same as the existing full path', function (): void {
    expect(FilePath::new(__DIR__, 'yolo')->relativeFrom(__DIR__ . '/yolo'))->toEqual('');
});

it("can check it's existence", function (): void {
    expect(FilePath::new(__DIR__, 'yolo.php')->exists())->toEqual(false);
    expect(FilePath::new(__DIR__, 'FilePathTest.php')->exists())->toEqual(true);
});

it("can check if it's a directory", function (): void {
    expect(FilePath::new(__DIR__, 'yolo.php')->isDirectory())->toEqual(false);
    expect(FilePath::new(__DIR__, 'FilePathTest.php')->isDirectory())->toEqual(false);
    expect(FilePath::new(__DIR__, '')->isDirectory())->toEqual(true);
    expect(FilePath::new(dirname(__DIR__), 'LocaleFilesystem')->isDirectory())->toEqual(true);
});

it("can check if it's a file", function (): void {
    expect(FilePath::new(__DIR__, 'yolo.php')->isFile())->toEqual(false);
    expect(FilePath::new(__DIR__, 'FilePathTest.php')->isFile())->toEqual(true);
    expect(FilePath::new(__DIR__, '')->isFile())->toEqual(false);
    expect(FilePath::new(dirname(__DIR__), 'LocaleFilesystem')->isFile())->toEqual(false);
    expect(FilePath::new(__FILE__)->isFile())->toEqual(true);
});

it("can check if it's nested within a given root path", function (): void {
    expect(FilePath::new(__DIR__)->isNestedWithin(dirname(__DIR__)))->toEqual(true);
    expect(FilePath::new(__DIR__)->isNestedWithin(dirname(__DIR__, 2)))->toEqual(true);
    expect(FilePath::new(__DIR__)->isNestedWithin(dirname(__DIR__, 3)))->toEqual(true);
    expect(FilePath::new(__DIR__, 'yolo.php')->isNestedWithin(__DIR__))->toEqual(true);

    expect(FilePath::new(__DIR__)->isNestedWithin(__DIR__))->toEqual(false);
    expect(FilePath::new(dirname(__DIR__))->isNestedWithin(__DIR__))->toEqual(false);
    expect(FilePath::new(dirname(__DIR__, 2))->isNestedWithin(__DIR__))->toEqual(false);
    expect(FilePath::new(dirname(__DIR__, 3))->isNestedWithin(__DIR__))->toEqual(false);
});

it('implements the "Arrayable" contract', function (): void {
    $path = FilePath::new(__DIR__, 'yolo.php');

    expect($path->toArray())->toEqual([
        'root' => $path->root(),
        'relative_path' => $path->relativePath(),
        'directory_separator' => $path->directorySeparator(),
        'full_path' => $path->fullPath(),
    ]);
});
