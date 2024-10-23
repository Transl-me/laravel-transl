<?php

declare(strict_types=1);

use Transl\Facades\Transl;
use Transl\Support\Branch;
use Transl\Support\Analysis\ProjectAnalysis;

it('works', function (): void {
    $project = Transl::config()->projects()->first();
    $branch = Branch::asCurrent('yolo');

    $sets = app($project->drivers->toBase()->keys()->first())->getTranslationSets($project, $branch);

    expect(ProjectAnalysis::fromTranslationSets($sets))->toMatchSnapshot();
});
