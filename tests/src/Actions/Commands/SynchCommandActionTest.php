<?php

declare(strict_types=1);

use Transl\Facades\Transl;
use Transl\Support\Branch;
use Transl\Support\Push\PushBatch;
use Transl\Config\ProjectConfiguration;
use Transl\Actions\Commands\PullCommandAction;
use Transl\Actions\Commands\PushCommandAction;
use Transl\Actions\Commands\SynchCommandAction;
use Transl\Actions\Commands\AbstractCommandAction;
use Transl\Config\Enums\BranchingConflictResolutionEnum;

beforeEach(function (): void {
    app()->bind(PullCommandAction::class, function (): PullCommandAction {
        return new class () extends PullCommandAction {
            public function execute(
                ProjectConfiguration $project,
                Branch $branch,
                ?BranchingConflictResolutionEnum $conflictResolution = null,
            ): void {
                //
            }
        };
    });
    app()->bind(PushCommandAction::class, function (): PushCommandAction {
        return new class () extends PushCommandAction {
            public function execute(
                ProjectConfiguration $project,
                Branch $branch,
                ?PushBatch $batch = null,
                array $meta = [],
            ): void {
                //
            }
        };
    });
});

it('extends `AbstractCommandAction`', function (): void {
    expect(is_subclass_of(PullCommandAction::class, AbstractCommandAction::class))->toEqual(true);
});

it('executes for a given project', function (): void {
    $action = (new SynchCommandAction());

    $project = Transl::config()->projects()->first();
    $branch = Branch::asCurrent('yolo');

    $action->execute($project, $branch);

    expect($action->project()->auth_key)->toEqual($project->auth_key);
});

it('executes for a given branch', function (): void {
    $action = (new SynchCommandAction());

    $project = Transl::config()->projects()->first();
    $branch = Branch::asCurrent('yolo');

    $action->execute($project, $branch);

    expect($action->branch()->name)->toEqual($branch->name);
});

it('uses `PullCommandAction` to pull translation sets from Transl', function (): void {
    $pullAction = app(PullCommandAction::class);

    $used = false;

    app()->bind(PullCommandAction::class, function () use (&$used, $pullAction): PullCommandAction {
        $used = true;

        return $pullAction;
    });

    (new SynchCommandAction())->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    expect($used)->toEqual(true);
});

it('uses `PushCommandAction` to push translation sets to Transl', function (): void {
    $pushAction = app(PushCommandAction::class);

    $used = false;

    app()->bind(PushCommandAction::class, function () use (&$used, $pushAction): PushCommandAction {
        $used = true;

        return $pushAction;
    });

    (new SynchCommandAction())->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    expect($used)->toEqual(true);
});

test('misc.', function (): void {
    (new SynchCommandAction())
        ->onPulledTranslationSetSkipped(static fn () => null)
        ->onPulledTranslationSetHandled(static fn () => null)
        ->onPushedTranslationSetSkipped(static fn () => null)
        ->onPushedTranslationSetHandled(static fn () => null)
        ->onIncomingTranslationSetConflicts(static fn () => null)
        ->silenceConflictExceptions()
        ->execute(Transl::config()->projects()->first(), Branch::asCurrent('yolo'));

    expect(true)->toEqual(true);
});
