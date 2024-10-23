<?php

declare(strict_types=1);

use Pest\Expectation;
use Transl\Tests\TestCase;
use Transl\Tests\TestSupport\app\Helpers\SnapshotExpectationHelper;

uses(TestCase::class)->in(__DIR__);

expect()->extend('toMatchSnapshotStandardizedUsing', function (Closure $callback, string $message = ''): Expectation {
    return expect($callback($this))->toMatchSnapshot($message);
});

expect()->extend('toMatchStandardizedSnapshot', function (string $message = ''): Expectation {
    $callback = static function (Expectation $expectation): mixed {
        return SnapshotExpectationHelper::new($expectation)->handle();
    };

    return expect($this->value)->toMatchSnapshotStandardizedUsing($callback, $message);
});

// expect()->extend('toMatchConsoleOutput', function (string $output = ''): Expectation {
//     $callback = function (Expectation $expectation): string {
//         return str($expectation->value)
//             ->replace(["\r\n", "\r"], "\n")
//             ->replace(['░', '▓', '.'], '')
//             ->replaceMatches('/[0-9]+\s\[/', '')
//             ->replace([']', "    \n"], '')
//             ->replace("\n\n", "\n")
//             ->replace(' ', '')
//             ->value();
//     };

//     return expect($this->value)->toMatchSnapshotStandardizedUsing($callback, $output);
// });
