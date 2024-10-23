<?php

declare(strict_types=1);

namespace Transl\Tests\TestSupport\app\Helpers;

use Transl\Tests\TestSupport\app\Helpers\TranslationSetHelper;

class Helpers
{
    public static function new(): static
    {
        return new static();
    }

    public function translationSet(): TranslationSetHelper
    {
        return TranslationSetHelper::new();
    }

    // public function snapshotExpectation(Expectation $expectation): SnapshotExpectationHelper
    // {
    //     return SnapshotExpectationHelper::new($expectation);
    // }
}
